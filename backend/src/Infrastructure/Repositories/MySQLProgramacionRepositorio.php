<?php
declare(strict_types=1);
namespace App\Infrastructure\Repositories;

use App\Dominio\Repositorios\ProgramacionRepositorio;
use DomainException;
use Throwable;

final class MySQLProgramacionRepositorio extends MySQLRepositorioBase implements ProgramacionRepositorio
{
    public function listar(int $periodoId, ?int $cursoId): array
    {
        $sql = 'SELECT * FROM seccion WHERE id_periodo = ?';
        $params = [$periodoId];
        if ($cursoId !== null) { $sql .= ' AND id_curso = ?'; $params[] = $cursoId; }
        return $this->all($sql.' ORDER BY codigo, id_seccion', $params);
    }

    public function consultar(int $id): ?array
    {
        $seccion = $this->one('SELECT * FROM seccion WHERE id_seccion = ?', [$id]);
        if ($seccion !== null) {
            $seccion['horarios'] = $this->all('SELECT * FROM horario WHERE id_seccion = ? ORDER BY dia_semana, hora_inicio', [$id]);
        }
        return $seccion;
    }

    private function bloqueo(): string
    {
        return $this->db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
    }

    private function transaccion(callable $operacion): array
    {
        $this->db->beginTransaction();
        try {
            $resultado = $operacion();
            $this->db->commit();
            return $resultado;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function crearSeccion(array $datos): array
    {
        return $this->transaccion(function () use ($datos): array {
            // Serializa códigos del mismo periodo antes de comprobar duplicados.
            if (!$this->one('SELECT id_periodo FROM periodo_academico WHERE id_periodo = ?'.$this->bloqueo(), [$datos['id_periodo']])) {
                throw new DomainException('El periodo no existe.');
            }
            if (!$this->one('SELECT id_curso FROM curso WHERE id_curso = ? AND estado = 1', [$datos['id_curso']])) {
                throw new DomainException('El curso no existe o está inactivo.');
            }
            if (!$this->one('SELECT d.id_usuario FROM docente d JOIN usuario u ON u.id_usuario = d.id_usuario WHERE d.id_usuario = ? AND u.estado = 1', [$datos['id_docente']])) {
                throw new DomainException('El docente no existe o está inactivo.');
            }
            if ($this->one('SELECT id_seccion FROM seccion WHERE id_periodo = ? AND codigo = ?', [$datos['id_periodo'], $datos['codigo']])) {
                throw new DomainException('Ya existe una sección con ese código en el periodo.');
            }
            $this->exec('INSERT INTO seccion (id_curso,id_periodo,id_docente,codigo,vacantes,vacantes_disponibles) VALUES (?,?,?,?,?,?)',
                [$datos['id_curso'],$datos['id_periodo'],$datos['id_docente'],$datos['codigo'],$datos['vacantes'],$datos['vacantes']]);
            return $this->consultar($this->generatedId());
        });
    }

    public function crearHorario(int $seccionId, array $datos): array
    {
        return $this->transaccion(function () use ($seccionId, $datos): array {
            $seccion = $this->one('SELECT * FROM seccion WHERE id_seccion = ?'.$this->bloqueo(), [$seccionId]);
            if (!$seccion) { throw new DomainException('La sección no existe.'); }
            // La matrícula también bloquea la sección; impide cambiar horarios de alumnos inscritos.
            if ($this->one("SELECT id_detalle FROM detalle_matricula WHERE id_seccion = ? AND estado <> 'ANULADO'", [$seccionId])) {
                throw new DomainException('No se pueden agregar horarios a una sección con matrículas.');
            }
            $this->one('SELECT id_usuario FROM docente WHERE id_usuario = ?'.$this->bloqueo(), [$seccion['id_docente']]);
            $aula = $this->one('SELECT * FROM aula WHERE id_aula = ?'.$this->bloqueo(), [$datos['id_aula']]);
            if (!$aula || !$aula['estado'] || !$aula['disponible']) { throw new DomainException('El aula no está disponible.'); }
            if ((int)$aula['capacidad'] < (int)$seccion['vacantes']) { throw new DomainException('La capacidad del aula es menor que las vacantes.'); }
            // Lectura con bloqueo: ve escrituras confirmadas tras esperar aula/docente en MySQL.
            $cruce = $this->one('SELECT h.id_horario FROM horario h JOIN seccion s ON s.id_seccion = h.id_seccion
                WHERE s.id_periodo = ? AND h.dia_semana = ? AND h.hora_inicio < ? AND h.hora_fin > ?
                AND (h.id_aula = ? OR s.id_docente = ? OR s.id_seccion = ?) LIMIT 1'.$this->bloqueo(),
                [$seccion['id_periodo'],$datos['dia_semana'],$datos['hora_fin'],$datos['hora_inicio'],$datos['id_aula'],$seccion['id_docente'],$seccionId]);
            if ($cruce) { throw new DomainException('El horario se cruza con otra reserva del aula, docente o sección.'); }
            $this->exec('INSERT INTO horario (id_seccion,id_aula,dia_semana,hora_inicio,hora_fin,modalidad) VALUES (?,?,?,?,?,?)',
                [$seccionId,$datos['id_aula'],$datos['dia_semana'],$datos['hora_inicio'],$datos['hora_fin'],$datos['modalidad']]);
            return $this->one('SELECT * FROM horario WHERE id_horario = ?', [$this->generatedId()]);
        });
    }
}

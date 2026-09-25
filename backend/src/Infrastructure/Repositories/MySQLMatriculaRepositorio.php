<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\DetalleMatricula;
use App\Dominio\Entidades\Matricula;
use App\Dominio\Repositorios\MatriculaRepositorio;

final class MySQLMatriculaRepositorio
    extends MySQLRepositorioBase
    implements MatriculaRepositorio
{
    public function buscarPorId(int $idMatricula): ?Matricula
    {
        $sql = "
            SELECT
                id_matricula,
                id_estudiante,
                id_periodo,
                codigo_matricula,
                fecha_matricula,
                estado,
                total_creditos
            FROM matricula
            WHERE id_matricula = :id_matricula
            LIMIT 1
        ";

        $resultado = $this->one($sql, [
            ':id_matricula' => $idMatricula
        ]);

        return $resultado
            ? $this->map($resultado)
            : null;
    }

    public function buscarPorEstudiante(int $idEstudiante): array
    {
        $sql = "
            SELECT
                id_matricula,
                id_estudiante,
                id_periodo,
                codigo_matricula,
                fecha_matricula,
                estado,
                total_creditos
            FROM matricula
            WHERE id_estudiante = :id_estudiante
            ORDER BY fecha_matricula DESC
        ";

        $resultados = $this->all($sql, [
            ':id_estudiante' => $idEstudiante
        ]);

        return array_map(
            fn(array $fila): Matricula => $this->map($fila),
            $resultados
        );
    }

    public function buscarPorPeriodo(int $idPeriodo): array
    {
        $sql = "
            SELECT
                id_matricula,
                id_estudiante,
                id_periodo,
                codigo_matricula,
                fecha_matricula,
                estado,
                total_creditos
            FROM matricula
            WHERE id_periodo = :id_periodo
            ORDER BY fecha_matricula DESC
        ";

        $resultados = $this->all($sql, [
            ':id_periodo' => $idPeriodo
        ]);

        return array_map(
            fn(array $fila): Matricula => $this->map($fila),
            $resultados
        );
    }

    public function buscarPorCodigo(string $codigo): ?Matricula
    {
        $resultado = $this->one(
            'SELECT id_matricula, id_estudiante, id_periodo, codigo_matricula, fecha_matricula, estado, total_creditos
             FROM matricula WHERE codigo_matricula = :codigo LIMIT 1',
            [':codigo' => $codigo]
        );

        return $resultado ? $this->map($resultado) : null;
    }

    public function listarPorEstudiante(int $idEstudiante): array
    {
        return $this->buscarPorEstudiante($idEstudiante);
    }

    public function guardar(Matricula $matricula): void
    {
        $sql = "
            INSERT INTO matricula (
                id_estudiante,
                id_periodo,
                codigo_matricula,
                fecha_matricula,
                estado,
                total_creditos
            )
            VALUES (
                :id_estudiante,
                :id_periodo,
                :codigo_matricula,
                :fecha_matricula,
                :estado,
                :total_creditos
            )
        ";

        $this->exec($sql, [
            ':id_estudiante' => $matricula->getIdEstudiante(),
            ':id_periodo' => $matricula->getIdPeriodo(),
            ':codigo_matricula' => (string) $matricula->getCodigoMatricula(),
            ':fecha_matricula' => $matricula->getFechaMatricula()->format('Y-m-d'),
            ':estado' => $matricula->getEstado(),
            ':total_creditos' => $matricula->getTotalCreditos()
        ]);
    }

    public function actualizar(Matricula $matricula): void
    {
        $sql = "
            UPDATE matricula
            SET
                id_estudiante = :id_estudiante,
                id_periodo = :id_periodo,
                codigo_matricula = :codigo_matricula,
                fecha_matricula = :fecha_matricula,
                estado = :estado,
                total_creditos = :total_creditos
            WHERE id_matricula = :id_matricula
        ";

        $this->exec($sql, [
            ':id_matricula' => $matricula->getIdMatricula(),
            ':id_estudiante' => $matricula->getIdEstudiante(),
            ':id_periodo' => $matricula->getIdPeriodo(),
            ':codigo_matricula' => (string) $matricula->getCodigoMatricula(),
            ':fecha_matricula' => $matricula->getFechaMatricula()->format('Y-m-d'),
            ':estado' => $matricula->getEstado(),
            ':total_creditos' => $matricula->getTotalCreditos()
        ]);
    }

    /** @param DetalleMatricula[] $detalles */
    public function registrarConDetalles(Matricula $matricula, array $detalles): int
    {
        $this->db->beginTransaction();

            try {
                // Serialize registrations for the same student, including duplicate requests.
                $lock = $this->db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
                $student = $this->one('SELECT id_usuario FROM estudiante WHERE id_usuario=:id' . $lock, ['id'=>$matricula->getIdEstudiante()]);
                if (!$student) throw new \DomainException('Estudiante inexistente.');
                $existing = $this->one("SELECT id_matricula FROM matricula WHERE id_estudiante=:e AND id_periodo=:p AND estado='REGISTRADA'", ['e'=>$matricula->getIdEstudiante(),'p'=>$matricula->getIdPeriodo()]);
                if ($existing) throw new \DomainException('Ya existe una matrícula activa en este periodo.');
                usort($detalles, fn($a,$b)=>$a->getIdSeccion()<=>$b->getIdSeccion());
                $ids = array_map(fn($d)=>$d->getIdSeccion(),$detalles);
                if (!$ids) throw new \DomainException('Seleccione al menos una sección.');
                $marks = implode(',', array_fill(0,count($ids),'?'));
                $sections = $this->all("SELECT * FROM seccion WHERE id_seccion IN ($marks) ORDER BY id_seccion" . $lock, $ids);
                if (count($sections)!==count($ids)) throw new \DomainException('Sección inexistente o repetida.');
                $courses=[];
                foreach ($sections as $s) {
                    if ((int)$s['id_periodo']!==$matricula->getIdPeriodo() || (int)$s['vacantes_disponibles']<=0) throw new \DomainException('Sección fuera del periodo o sin vacantes.');
                    if (isset($courses[$s['id_curso']])) throw new \DomainException('Curso repetido.');
                    $courses[$s['id_curso']]=true;
                    $missing = $this->one("SELECT pr.id_curso_requerido FROM prerequisito pr WHERE pr.id_curso=:curso AND NOT EXISTS (
                        SELECT 1 FROM detalle_matricula d JOIN matricula m ON m.id_matricula=d.id_matricula JOIN seccion s ON s.id_seccion=d.id_seccion
                        WHERE m.id_estudiante=:estudiante AND m.estado<>'ANULADA' AND d.estado='APROBADO' AND s.id_curso=pr.id_curso_requerido)", ['curso'=>$s['id_curso'],'estudiante'=>$matricula->getIdEstudiante()]);
                    if ($missing) throw new \DomainException('Falta aprobar un prerrequisito.');
                }
                $horarios=$this->all("SELECT * FROM horario WHERE id_seccion IN ($marks)",$ids);
                foreach ($horarios as $i=>$a) foreach (array_slice($horarios,$i+1) as $b) {
                    if ($a['id_seccion']!==$b['id_seccion'] && $a['dia_semana']===$b['dia_semana'] && $a['hora_inicio']<$b['hora_fin'] && $b['hora_inicio']<$a['hora_fin']) throw new \DomainException('Las secciones presentan cruce de horarios.');
                }
                $this->exec(
                    "INSERT INTO matricula (id_estudiante, id_periodo, codigo_matricula, fecha_matricula, estado, total_creditos)
                     VALUES (:id_estudiante, :id_periodo, :codigo_matricula, :fecha_matricula, :estado, :total_creditos)",
                    [
                        ':id_estudiante' => $matricula->getIdEstudiante(),
                        ':id_periodo' => $matricula->getIdPeriodo(),
                        ':codigo_matricula' => (string) $matricula->getCodigoMatricula(),
                        ':fecha_matricula' => $matricula->getFechaMatricula()->format('Y-m-d'),
                        ':estado' => $matricula->getEstado(),
                        ':total_creditos' => $matricula->getTotalCreditos(),
                    ]
                );

                $matriculaId = $this->generatedId();

                foreach ($detalles as $detalle) {
                    $detalle->setIdMatricula($matriculaId);

                    $cupo = $this->one(
                        'SELECT vacantes_disponibles FROM seccion WHERE id_seccion = :id_seccion' . $lock,
                        [':id_seccion' => $detalle->getIdSeccion()]
                    );
                    if (!$cupo || (int) $cupo['vacantes_disponibles'] <= 0) {
                        throw new \DomainException('No hay vacantes disponibles para una de las secciones seleccionadas.');
                    }

                    $this->exec(
                        'INSERT INTO detalle_matricula (id_matricula, id_seccion, estado)
                         VALUES (:id_matricula, :id_seccion, :estado)',
                        [
                            ':id_matricula' => $matriculaId,
                            ':id_seccion' => $detalle->getIdSeccion(),
                            ':estado' => $detalle->getEstado(),
                        ]
                    );

                    $detalle->setIdDetalle($this->generatedId());
                    $this->exec(
                        'UPDATE seccion SET vacantes_disponibles = vacantes_disponibles - 1
                         WHERE id_seccion = :id_seccion AND vacantes_disponibles > 0',
                        [':id_seccion' => $detalle->getIdSeccion()]
                    );
                }

                $this->db->commit();
                return $matriculaId;
            } catch (\Throwable $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                throw $e;
            }
    }

    private function map(array $fila): Matricula
    {
        $matricula = new Matricula(
            (int) $fila['id_matricula'],
            (int) $fila['id_estudiante'],
            (int) $fila['id_periodo'],
            new \DateTimeImmutable($fila['fecha_matricula']),
            (string) $fila['estado'],
            (int) $fila['total_creditos']
        );

        if (!empty($fila['codigo_matricula'])) {
            $matricula->setCodigoMatricula(new \App\Dominio\ValueObjects\CodigoMatricula((string) $fila['codigo_matricula']));
        }

        $detalles=$this->all('SELECT * FROM detalle_matricula WHERE id_matricula=:id',['id'=>$matricula->getIdMatricula()]);
        $matricula->setDetalles(array_map(fn($d)=>new DetalleMatricula((int)$d['id_detalle'],(int)$d['id_matricula'],(int)$d['id_seccion'],$d['estado']),$detalles));
        return $matricula;
    }

    public function anularConDetalles(int $idMatricula): void {
        $this->db->beginTransaction();
        try {
            $lock=$this->db->getAttribute(\PDO::ATTR_DRIVER_NAME)==='mysql'?' FOR UPDATE':'';
            $m=$this->one('SELECT * FROM matricula WHERE id_matricula=:id'.$lock,['id'=>$idMatricula]);
            if(!$m || $m['estado']!=='REGISTRADA') throw new \DomainException('La matrícula no existe o no puede anularse.');
            $detalles=$this->all("SELECT * FROM detalle_matricula WHERE id_matricula=:id AND estado='MATRICULADO' ORDER BY id_seccion",['id'=>$idMatricula]);
            foreach($detalles as $d) {
                $this->exec('UPDATE seccion SET vacantes_disponibles=vacantes_disponibles+1 WHERE id_seccion=:id AND vacantes_disponibles<vacantes',['id'=>$d['id_seccion']]);
            }
            $this->exec("UPDATE detalle_matricula SET estado='ANULADO' WHERE id_matricula=:id AND estado='MATRICULADO'",['id'=>$idMatricula]);
            $this->exec("UPDATE matricula SET estado='ANULADA' WHERE id_matricula=:id",['id'=>$idMatricula]);
            $this->db->commit();
        } catch (\Throwable $e) { if($this->db->inTransaction())$this->db->rollBack(); throw $e; }
    }

}

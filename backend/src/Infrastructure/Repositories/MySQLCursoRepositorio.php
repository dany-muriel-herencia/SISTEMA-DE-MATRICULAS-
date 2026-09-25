<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\Curso;
use App\Dominio\Repositorios\CursoRepositorio;

final class MySQLCursoRepositorio
    extends MySQLRepositorioBase
    implements CursoRepositorio
{
    public function buscarPorId(int $idCurso): ?Curso
    {
        $sql = "
            SELECT
                id_curso,
                nombre,
                codigo,
                creditos,
                horas_teoria,
                horas_practica,
                ciclo,
                estado
            FROM curso
            WHERE id_curso = :id_curso
            LIMIT 1
        ";

        $resultado = $this->one($sql, [
            ':id_curso' => $idCurso
        ]);

        return $resultado
            ? $this->map($resultado)
            : null;
    }

    public function buscarPorCodigo(string $codigo): ?Curso
    {
        $sql = "
            SELECT
                id_curso,
                nombre,
                codigo,
                creditos,
                horas_teoria,
                horas_practica,
                ciclo,
                estado
            FROM curso
            WHERE codigo = :codigo
            LIMIT 1
        ";

        $resultado = $this->one($sql, [
            ':codigo' => $codigo
        ]);

        return $resultado
            ? $this->map($resultado)
            : null;
    }

    public function listarActivos(): array
    {
        $sql = "
            SELECT
                id_curso,
                nombre,
                codigo,
                creditos,
                horas_teoria,
                horas_practica,
                ciclo,
                estado
            FROM curso
            WHERE estado = 1
            ORDER BY ciclo ASC, nombre ASC
        ";

        $resultados = $this->all($sql);

        return array_map(
            fn(array $fila): Curso => $this->map($fila),
            $resultados
        );
    }

    public function guardar(Curso $curso): void
    {
        $sql = "
            INSERT INTO curso (
                nombre,
                codigo,
                creditos,
                horas_teoria,
                horas_practica,
                ciclo,
                estado
            )
            VALUES (
                :nombre,
                :codigo,
                :creditos,
                :horas_teoria,
                :horas_practica,
                :ciclo,
                :estado
            )
        ";

        $this->exec($sql, [
            ':nombre' => $curso->getNombre(),
            ':codigo' => $curso->getCodigo(),
            ':creditos' => $curso->getCreditos(),
            ':horas_teoria' => $curso->getHorasTeoria(),
            ':horas_practica' => $curso->getHorasPractica(),
            ':ciclo' => $curso->getCiclo(),
            ':estado' => $curso->getEstado()
        ]);
        $curso->setIdCurso($this->generatedId());
    }

    public function actualizar(Curso $curso): void
    {
        $sql = "
            UPDATE curso
            SET
                nombre = :nombre,
                codigo = :codigo,
                creditos = :creditos,
                horas_teoria = :horas_teoria,
                horas_practica = :horas_practica,
                ciclo = :ciclo,
                estado = :estado
            WHERE id_curso = :id_curso
        ";

        $this->exec($sql, [
            ':id_curso' => $curso->getIdCurso(),
            ':nombre' => $curso->getNombre(),
            ':codigo' => $curso->getCodigo(),
            ':creditos' => $curso->getCreditos(),
            ':horas_teoria' => $curso->getHorasTeoria(),
            ':horas_practica' => $curso->getHorasPractica(),
            ':ciclo' => $curso->getCiclo(),
            ':estado' => $curso->getEstado()
        ]);
    }

    private function map(array $fila): Curso
    {
        return new Curso(
            (int) $fila['id_curso'],
            (string) $fila['nombre'],
            (string) $fila['codigo'],
            (int) $fila['creditos'],
            (int) $fila['horas_teoria'],
            (int) $fila['horas_practica'],
            (string) $fila['ciclo'],
            (bool) $fila['estado']
        );
    }

    public function listar(int $limit=50,int $offset=0): array {
        $limit=max(1,min(200,$limit)); $offset=max(0,$offset);
        return array_map(fn($r)=>$this->map($r),$this->all("SELECT * FROM curso ORDER BY codigo LIMIT $limit OFFSET $offset"));
    }
    public function listarOfertaPorPeriodoYCarrera(int $periodo,int $carrera): array {
        return $this->all('SELECT DISTINCT s.*, c.nombre, c.creditos FROM seccion s JOIN curso c ON c.id_curso=s.id_curso JOIN curriculum cu ON cu.id_curso=c.id_curso JOIN plan_estudio p ON p.id_plan=cu.id_plan WHERE s.id_periodo=:periodo AND p.id_carrera=:carrera AND p.estado=1 AND c.estado=1 ORDER BY c.nombre,s.codigo',['periodo'=>$periodo,'carrera'=>$carrera]);
    }

}

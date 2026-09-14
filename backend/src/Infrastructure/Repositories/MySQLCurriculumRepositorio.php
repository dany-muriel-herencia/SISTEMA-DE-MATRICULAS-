<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\Curriculum;
use App\Dominio\Repositorios\CurriculumRepositorio;

final class MySQLCurriculumRepositorio
    extends MySQLRepositorioBase
    implements CurriculumRepositorio
{
    public function buscarPorId(
        int $idCurriculum
    ): ?Curriculum {
        $sql = "
            SELECT
                id_curriculum,
                id_plan,
                id_curso,
                ciclo,
                obligatorio
            FROM curriculum
            WHERE id_curriculum = :id_curriculum
            LIMIT 1
        ";

        $resultado = $this->one(
            $sql,
            [
                ':id_curriculum' => $idCurriculum
            ]
        );

        return $resultado
            ? $this->map($resultado)
            : null;
    }

    public function listar(): array
    {
        $sql = "
            SELECT
                id_curriculum,
                id_plan,
                id_curso,
                ciclo,
                obligatorio
            FROM curriculum
            ORDER BY ciclo ASC
        ";

        $resultados = $this->all($sql);

        return array_map(
            fn(array $fila): Curriculum =>
                $this->map($fila),
            $resultados
        );
    }

    public function guardar(
        Curriculum $curriculum
    ): void {
        $sql = "
            INSERT INTO curriculum (
                id_plan,
                id_curso,
                ciclo,
                obligatorio
            )
            VALUES (
                :id_plan,
                :id_curso,
                :ciclo,
                :obligatorio
            )
        ";

        $this->exec(
            $sql,
            [
                ':id_plan' =>
                    $curriculum->getIdPlan(),

                ':id_curso' =>
                    $curriculum->getIdCurso(),

                ':ciclo' =>
                    $curriculum->getCiclo(),

                ':obligatorio' =>
                    $curriculum->isObligatorio()
            ]
        );
    }

    public function actualizar(
        Curriculum $curriculum
    ): void {
        $sql = "
            UPDATE curriculum
            SET
                id_plan = :id_plan,
                id_curso = :id_curso,
                ciclo = :ciclo,
                obligatorio = :obligatorio
            WHERE id_curriculum = :id_curriculum
        ";

        $this->exec(
            $sql,
            [
                ':id_curriculum' =>
                    $curriculum->getIdCurriculum(),

                ':id_plan' =>
                    $curriculum->getIdPlan(),

                ':id_curso' =>
                    $curriculum->getIdCurso(),

                ':ciclo' =>
                    $curriculum->getCiclo(),

                ':obligatorio' =>
                    $curriculum->isObligatorio()
            ]
        );
    }

    private function map(array $fila): Curriculum
    {
        return new Curriculum(
            (int) $fila['id_curriculum'],
            (int) $fila['id_plan'],
            (int) $fila['id_curso'],
            (int) $fila['ciclo'],
            (bool) $fila['obligatorio']
        );
    }
}
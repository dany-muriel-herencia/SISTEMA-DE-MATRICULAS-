<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\Seccion;
use App\Dominio\Repositorios\SeccionRepositorio;
use RuntimeException;

final class MySQLSeccionRepositorio
    extends MySQLRepositorioBase
    implements SeccionRepositorio
{

    public function buscarPorId(int $idSeccion): ?Seccion
    {
        $sql = "
            SELECT
                id_seccion,
                id_curso,
                id_periodo,
                id_docente,
                codigo,
                vacantes,
                vacantes_disponibles
            FROM seccion
            WHERE id_seccion = :id_seccion
            LIMIT 1
        ";

        $resultado = $this->one(
            $sql,
            [
                ':id_seccion' => $idSeccion
            ]
        );

        return $resultado
            ? $this->map($resultado)
            : null;
    }


    public function listarDisponibles(): array
    {
        $sql = "
            SELECT
                id_seccion,
                id_curso,
                id_periodo,
                id_docente,
                codigo,
                vacantes,
                vacantes_disponibles
            FROM seccion
            WHERE vacantes_disponibles > 0
            ORDER BY codigo ASC
        ";

        $resultados = $this->all($sql);

        return array_map(
            fn(array $fila): Seccion => $this->map($fila),
            $resultados
        );
    }


    public function guardar(Seccion $seccion): void
    {
        $sql = "
            INSERT INTO seccion (
                id_curso,
                id_periodo,
                id_docente,
                codigo,
                vacantes,
                vacantes_disponibles
            )
            VALUES (
                :id_curso,
                :id_periodo,
                :id_docente,
                :codigo,
                :vacantes,
                :vacantes_disponibles
            )
        ";

        try {
            $this->exec(
                $sql,
                [
                    ':id_curso' => $seccion->getIdCurso(),
                    ':id_periodo' => $seccion->getIdPeriodo(),
                    ':id_docente' => $seccion->getIdDocente(),
                    ':codigo' => $seccion->getCodigo(),
                    ':vacantes' => $seccion->getVacantes(),
                    ':vacantes_disponibles' => $seccion->getVacantesDisponibles()
                ]
            );
        } catch (\PDOException $e) {
            throw new RuntimeException(
                'Error al guardar la sección: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }


    public function actualizar(Seccion $seccion): void
    {
        $sql = "
            UPDATE seccion
            SET
                id_curso = :id_curso,
                id_periodo = :id_periodo,
                id_docente = :id_docente,
                codigo = :codigo,
                vacantes = :vacantes,
                vacantes_disponibles = :vacantes_disponibles
            WHERE id_seccion = :id_seccion
        ";

        try {
            $this->exec(
                $sql,
                [
                    ':id_seccion' => $seccion->getIdSeccion(),
                    ':id_curso' => $seccion->getIdCurso(),
                    ':id_periodo' => $seccion->getIdPeriodo(),
                    ':id_docente' => $seccion->getIdDocente(),
                    ':codigo' => $seccion->getCodigo(),
                    ':vacantes' => $seccion->getVacantes(),
                    ':vacantes_disponibles' => $seccion->getVacantesDisponibles()
                ]
            );
        } catch (\PDOException $e) {
            throw new RuntimeException(
                'Error al actualizar la sección: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }


    private function map(array $fila): Seccion
    {
        return new Seccion(
            (int) $fila['id_seccion'],
            (int) $fila['id_curso'],
            (int) $fila['id_periodo'],
            (int) $fila['id_docente'],
            (string) $fila['codigo'],
            (int) $fila['vacantes'],
            (int) $fila['vacantes_disponibles']
        );
    }
}
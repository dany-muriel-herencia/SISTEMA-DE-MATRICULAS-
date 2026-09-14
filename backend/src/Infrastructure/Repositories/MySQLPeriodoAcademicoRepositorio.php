<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\PeriodoAcademico;
use App\Dominio\Repositorios\PeriodoAcademicoRepositorio;
use DateTimeImmutable;

final class MySQLPeriodoAcademicoRepositorio
    extends MySQLRepositorioBase
    implements PeriodoAcademicoRepositorio
{
    public function buscarPorId(int $idPeriodo): ?PeriodoAcademico
    {
        $sql = "
            SELECT
                id_periodo,
                nombre,
                fecha_inicio,
                fecha_fin,
                fecha_matricula_inicio,
                fecha_matricula_fin,
                estado
            FROM periodo_academico
            WHERE id_periodo = :id_periodo
            LIMIT 1
        ";

        $resultado = $this->one($sql, [
            ':id_periodo' => $idPeriodo
        ]);

        return $resultado
            ? $this->map($resultado)
            : null;
    }

    public function obtenerPeriodoActivo(): ?PeriodoAcademico
    {
        $sql = "
            SELECT
                id_periodo,
                nombre,
                fecha_inicio,
                fecha_fin,
                fecha_matricula_inicio,
                fecha_matricula_fin,
                estado
            FROM periodo_academico
            WHERE estado = 1
            ORDER BY fecha_inicio DESC
            LIMIT 1
        ";

        $resultado = $this->one($sql);

        return $resultado
            ? $this->map($resultado)
            : null;
    }

    public function guardar(PeriodoAcademico $periodo): void
    {
        $sql = "
            INSERT INTO periodo_academico (
                nombre,
                fecha_inicio,
                fecha_fin,
                fecha_matricula_inicio,
                fecha_matricula_fin,
                estado
            )
            VALUES (
                :nombre,
                :fecha_inicio,
                :fecha_fin,
                :fecha_matricula_inicio,
                :fecha_matricula_fin,
                :estado
            )
        ";

        $this->exec($sql, [
            ':nombre' => $periodo->getNombre(),
            ':fecha_inicio' => $periodo->getFechaInicio()->format('Y-m-d'),
            ':fecha_fin' => $periodo->getFechaFin()->format('Y-m-d'),
            ':fecha_matricula_inicio' => $periodo->getFechaMatriculaInicio()->format('Y-m-d'),
            ':fecha_matricula_fin' => $periodo->getFechaMatriculaFin()->format('Y-m-d'),
            ':estado' => $periodo->getEstado()
        ]);
    }

    public function actualizar(PeriodoAcademico $periodo): void
    {
        $sql = "
            UPDATE periodo_academico
            SET
                nombre = :nombre,
                fecha_inicio = :fecha_inicio,
                fecha_fin = :fecha_fin,
                fecha_matricula_inicio = :fecha_matricula_inicio,
                fecha_matricula_fin = :fecha_matricula_fin,
                estado = :estado
            WHERE id_periodo = :id_periodo
        ";

        $this->exec($sql, [
            ':id_periodo' => $periodo->getIdPeriodo(),
            ':nombre' => $periodo->getNombre(),
            ':fecha_inicio' => $periodo->getFechaInicio()->format('Y-m-d'),
            ':fecha_fin' => $periodo->getFechaFin()->format('Y-m-d'),
            ':fecha_matricula_inicio' => $periodo->getFechaMatriculaInicio()->format('Y-m-d'),
            ':fecha_matricula_fin' => $periodo->getFechaMatriculaFin()->format('Y-m-d'),
            ':estado' => $periodo->getEstado()
        ]);
    }

    private function map(array $fila): PeriodoAcademico
    {
        return new PeriodoAcademico(
            (int) $fila['id_periodo'],
            (string) $fila['nombre'],
            new DateTimeImmutable($fila['fecha_inicio']),
            new DateTimeImmutable($fila['fecha_fin']),
            new DateTimeImmutable($fila['fecha_matricula_inicio']),
            new DateTimeImmutable($fila['fecha_matricula_fin']),
            (bool) $fila['estado']
        );
    }
}
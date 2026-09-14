<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\PlanEstudio;
use App\Dominio\Repositorios\PlanEstudioRepositorio;
use DateTimeImmutable;

final class MySQLPlanEstudioRepositorio
    extends MySQLRepositorioBase
    implements PlanEstudioRepositorio
{
    public function buscarPorId(int $idPlan): ?PlanEstudio
    {
        $sql = "
            SELECT
                id_plan,
                id_carrera,
                nombre,
                fecha_inicio,
                fecha_fin,
                estado
            FROM plan_estudio
            WHERE id_plan = :id_plan
            LIMIT 1
        ";

        $resultado = $this->one($sql, [
            ':id_plan' => $idPlan
        ]);

        return $resultado
            ? $this->map($resultado)
            : null;
    }

    public function listar(): array
    {
        $sql = "
            SELECT
                id_plan,
                id_carrera,
                nombre,
                fecha_inicio,
                fecha_fin,
                estado
            FROM plan_estudio
            ORDER BY nombre ASC
        ";

        $resultados = $this->all($sql);

        return array_map(
            fn(array $fila): PlanEstudio => $this->map($fila),
            $resultados
        );
    }

    public function guardar(PlanEstudio $planEstudio): void
    {
        $sql = "
            INSERT INTO plan_estudio (
                id_carrera,
                nombre,
                fecha_inicio,
                fecha_fin,
                estado
            )
            VALUES (
                :id_carrera,
                :nombre,
                :fecha_inicio,
                :fecha_fin,
                :estado
            )
        ";

        $this->exec($sql, [
            ':id_carrera' => $planEstudio->getIdCarrera(),
            ':nombre' => $planEstudio->getNombre(),
            ':fecha_inicio' => $planEstudio->getFechaInicio()->format('Y-m-d'),
            ':fecha_fin' => $planEstudio->getFechaFin()?->format('Y-m-d'),
            ':estado' => $planEstudio->getEstado()
        ]);
    }

    public function actualizar(PlanEstudio $planEstudio): void
    {
        $sql = "
            UPDATE plan_estudio
            SET
                id_carrera = :id_carrera,
                nombre = :nombre,
                fecha_inicio = :fecha_inicio,
                fecha_fin = :fecha_fin,
                estado = :estado
            WHERE id_plan = :id_plan
        ";

        $this->exec($sql, [
            ':id_plan' => $planEstudio->getIdPlan(),
            ':id_carrera' => $planEstudio->getIdCarrera(),
            ':nombre' => $planEstudio->getNombre(),
            ':fecha_inicio' => $planEstudio->getFechaInicio()->format('Y-m-d'),
            ':fecha_fin' => $planEstudio->getFechaFin()?->format('Y-m-d'),
            ':estado' => $planEstudio->getEstado()
        ]);
    }

    private function map(array $fila): PlanEstudio
    {
        return new PlanEstudio(
            (int) $fila['id_plan'],
            (int) $fila['id_carrera'],
            (string) $fila['nombre'],
            new DateTimeImmutable($fila['fecha_inicio']),
            $fila['fecha_fin'] !== null
                ? new DateTimeImmutable($fila['fecha_fin'])
                : null,
            (bool) $fila['estado']
        );
    }
}
<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\Aula;
use App\Dominio\Repositorios\AulaRepositorio;

final class MySQLAulaRepositorio
    extends MySQLRepositorioBase
    implements AulaRepositorio
{

    public function buscarPorId(int $idAula): ?Aula
    {
        $sql = "
            SELECT
                id_aula,
                nombre,
                ubicacion,
                capacidad,
                tipo,
                disponible,
                estado
            FROM aula
            WHERE id_aula = :id_aula
            LIMIT 1
        ";

        $r = $this->one(
            $sql,
            [
                ':id_aula' => $idAula
            ]
        );

        return $r ? $this->map($r) : null;
    }


    public function listar(): array
    {
        $sql = "
            SELECT
                id_aula,
                nombre,
                ubicacion,
                capacidad,
                tipo,
                disponible,
                estado
            FROM aula
            ORDER BY nombre ASC
        ";

        $resultados = $this->all($sql);

        return array_map(
            fn(array $r): Aula => $this->map($r),
            $resultados
        );
    }


    public function listarDisponibles(): array
    {
        $sql = "
            SELECT
                id_aula,
                nombre,
                ubicacion,
                capacidad,
                tipo,
                disponible,
                estado
            FROM aula
            WHERE disponible = TRUE
              AND estado = TRUE
            ORDER BY nombre ASC
        ";

        $resultados = $this->all($sql);

        return array_map(
            fn(array $r): Aula => $this->map($r),
            $resultados
        );
    }


    public function guardar(Aula $aula): void
    {
        $sql = "
            INSERT INTO aula (
                nombre,
                ubicacion,
                capacidad,
                tipo,
                disponible,
                estado
            )
            VALUES (
                :nombre,
                :ubicacion,
                :capacidad,
                :tipo,
                :disponible,
                :estado
            )
        ";

        $this->exec(
            $sql,
            [
                ':nombre' =>
                    $aula->getNombre(),

                ':ubicacion' =>
                    $aula->getUbicacion(),

                ':capacidad' =>
                    $aula->getCapacidad(),

                ':tipo' =>
                    $aula->getTipo(),

                ':disponible' =>
                    $aula->getDisponible(),

                ':estado' =>
                    $aula->getEstado()
            ]
        );
    }


    public function actualizar(Aula $aula): void
    {
        $sql = "
            UPDATE aula
            SET
                nombre = :nombre,
                ubicacion = :ubicacion,
                capacidad = :capacidad,
                tipo = :tipo,
                disponible = :disponible,
                estado = :estado
            WHERE id_aula = :id_aula
        ";

        $this->exec(
            $sql,
            [
                ':id_aula' =>
                    $aula->getIdAula(),

                ':nombre' =>
                    $aula->getNombre(),

                ':ubicacion' =>
                    $aula->getUbicacion(),

                ':capacidad' =>
                    $aula->getCapacidad(),

                ':tipo' =>
                    $aula->getTipo(),

                ':disponible' =>
                    $aula->getDisponible(),

                ':estado' =>
                    $aula->getEstado()
            ]
        );
    }


    private function map(array $r): Aula
    {
        return new Aula(
            (int) $r['id_aula'],
            (string) $r['nombre'],
            $r['ubicacion'] !== null
                ? $r['ubicacion']
                : null,
            (int) $r['capacidad'],
            $r['tipo'],
            (bool) $r['disponible'],
            (bool) $r['estado']
        );
    }
}
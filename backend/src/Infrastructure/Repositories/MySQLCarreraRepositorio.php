<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\Carrera;
use App\Dominio\Repositorios\CarreraRepositorio;

final class MySQLCarreraRepositorio
    extends MySQLRepositorioBase
    implements CarreraRepositorio
{
    public function buscarPorId(int $idCarrera): ?Carrera
    {
        $sql = "
            SELECT
                id_carrera,
                id_escuela,
                nombre,
                codigo,
                duracion,
                estado
            FROM carrera
            WHERE id_carrera = :id_carrera
            LIMIT 1
        ";

        $resultado = $this->one(
            $sql,
            [
                ':id_carrera' => $idCarrera
            ]
        );

        return $resultado
            ? $this->map($resultado)
            : null;
    }

    public function buscarPorCodigo(string $codigo): ?Carrera
    {
        $sql = "
            SELECT
                id_carrera,
                id_escuela,
                nombre,
                codigo,
                duracion,
                estado
            FROM carrera
            WHERE codigo = :codigo
            LIMIT 1
        ";

        $resultado = $this->one(
            $sql,
            [
                ':codigo' => $codigo
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
                id_carrera,
                id_escuela,
                nombre,
                codigo,
                duracion,
                estado
            FROM carrera
            ORDER BY nombre ASC
        ";

        $resultados = $this->all($sql);

        return array_map(
            fn(array $fila): Carrera => $this->map($fila),
            $resultados
        );
    }

    public function guardar(Carrera $carrera): void
    {
        $sql = "
            INSERT INTO carrera (
                id_escuela,
                nombre,
                codigo,
                duracion,
                estado
            )
            VALUES (
                :id_escuela,
                :nombre,
                :codigo,
                :duracion,
                :estado
            )
        ";

        $this->exec(
            $sql,
            [
                ':id_escuela' => $carrera->getIdEscuela(),
                ':nombre' => $carrera->getNombre(),
                ':codigo' => $carrera->getCodigo(),
                ':duracion' => $carrera->getDuracion(),
                ':estado' => $carrera->getEstado()
            ]
        );
    }

    public function actualizar(Carrera $carrera): void
    {
        $sql = "
            UPDATE carrera
            SET
                id_escuela = :id_escuela,
                nombre = :nombre,
                codigo = :codigo,
                duracion = :duracion,
                estado = :estado
            WHERE id_carrera = :id_carrera
        ";

        $this->exec(
            $sql,
            [
                ':id_carrera' => $carrera->getIdCarrera(),
                ':id_escuela' => $carrera->getIdEscuela(),
                ':nombre' => $carrera->getNombre(),
                ':codigo' => $carrera->getCodigo(),
                ':duracion' => $carrera->getDuracion(),
                ':estado' => $carrera->getEstado()
            ]
        );
    }

    private function map(array $fila): Carrera
    {
        return new Carrera(
            (int) $fila['id_carrera'],
            (int) $fila['id_escuela'],
            (string) $fila['nombre'],
            (string) $fila['codigo'],
            (int) $fila['duracion'],
            (bool) $fila['estado']
        );
    }
}
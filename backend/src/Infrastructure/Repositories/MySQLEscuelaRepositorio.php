<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\Escuela;
use App\Dominio\Repositorios\EscuelaRepositorio;

final class MySQLEscuelaRepositorio
    extends MySQLRepositorioBase
    implements EscuelaRepositorio
{
    public function buscarPorId(int $idEscuela): ?Escuela
    {
        $sql = "
            SELECT
                id_escuela,
                id_facultad,
                nombre,
                descripcion,
                director
            FROM escuela
            WHERE id_escuela = :id_escuela
            LIMIT 1
        ";

        $resultado = $this->one($sql, [
            ':id_escuela' => $idEscuela
        ]);

        return $resultado
            ? $this->map($resultado)
            : null;
    }

    public function listar(): array
    {
        $sql = "
            SELECT
                id_escuela,
                id_facultad,
                nombre,
                descripcion,
                director
            FROM escuela
            ORDER BY nombre ASC
        ";

        $resultados = $this->all($sql);

        return array_map(
            fn(array $fila): Escuela => $this->map($fila),
            $resultados
        );
    }

    public function guardar(Escuela $escuela): void
    {
        $sql = "
            INSERT INTO escuela (
                id_facultad,
                nombre,
                descripcion,
                director
            )
            VALUES (
                :id_facultad,
                :nombre,
                :descripcion,
                :director
            )
        ";

        $this->exec($sql, [
            ':id_facultad' => $escuela->getIdFacultad(),
            ':nombre' => $escuela->getNombre(),
            ':descripcion' => $escuela->getDescripcion(),
            ':director' => $escuela->getDirector()
        ]);
    }

    public function actualizar(Escuela $escuela): void
    {
        $sql = "
            UPDATE escuela
            SET
                id_facultad = :id_facultad,
                nombre = :nombre,
                descripcion = :descripcion,
                director = :director
            WHERE id_escuela = :id_escuela
        ";

        $this->exec($sql, [
            ':id_escuela' => $escuela->getIdEscuela(),
            ':id_facultad' => $escuela->getIdFacultad(),
            ':nombre' => $escuela->getNombre(),
            ':descripcion' => $escuela->getDescripcion(),
            ':director' => $escuela->getDirector()
        ]);
    }

    private function map(array $fila): Escuela
    {
        return new Escuela(
            (int) $fila['id_escuela'],
            (int) $fila['id_facultad'],
            (string) $fila['nombre'],
            (string) $fila['descripcion'],
            (string) $fila['director']
        );
    }
}
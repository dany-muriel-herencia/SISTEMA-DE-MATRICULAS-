<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\Facultad;
use App\Dominio\Repositorios\FacultadRepositorio;

final class MySQLFacultadRepositorio extends MySQLRepositorioBase implements FacultadRepositorio {
    public function buscarPorId(int $idFacultad): ?Facultad
    {
        $sql = "
            SELECT
                id_facultad,
                nombre,
                descripcion,
                decano
            FROM facultad
            WHERE id_facultad = :id_facultad
            LIMIT 1
        ";

        $resultado = $this->one($sql, [
            ':id_facultad' => $idFacultad
        ]);

        return $resultado
            ? $this->map($resultado)
            : null;
    }

    public function listar(): array
    {
        $sql = "
            SELECT
                id_facultad,
                nombre,
                descripcion,
                decano
            FROM facultad
            ORDER BY nombre ASC
        ";

        $resultados = $this->all($sql);

        return array_map(
            fn(array $fila): Facultad => $this->map($fila),
            $resultados
        );
    }

    public function guardar(Facultad $facultad): void
    {
        $sql = "
            INSERT INTO facultad (
                nombre,
                descripcion,
                decano
            )
            VALUES (
                :nombre,
                :descripcion,
                :decano
            )
        ";

        $this->exec($sql, [
            ':nombre' => $facultad->getNombre(),
            ':descripcion' => $facultad->getDescripcion(),
            ':decano' => $facultad->getDecano()
        ]);
    }

    public function actualizar(Facultad $facultad): void
    {
        $sql = "
            UPDATE facultad
            SET
                nombre = :nombre,
                descripcion = :descripcion,
                decano = :decano
            WHERE id_facultad = :id_facultad
        ";

        $this->exec($sql, [
            ':id_facultad' => $facultad->getIdFacultad(),
            ':nombre' => $facultad->getNombre(),
            ':descripcion' => $facultad->getDescripcion(),
            ':decano' => $facultad->getDecano()
        ]);
    }

    private function map(array $fila): Facultad
    {
        return new Facultad(
            (int) $fila['id_facultad'],
            (string) $fila['nombre'],
            (string) $fila['descripcion'],
            (string) $fila['decano']
        );
    }
}
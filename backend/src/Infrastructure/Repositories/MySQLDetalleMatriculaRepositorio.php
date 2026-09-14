<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\DetalleMatricula;
use App\Dominio\Repositorios\DetalleMatriculaRepositorio;

final class MySQLDetalleMatriculaRepositorio
    extends MySQLRepositorioBase
    implements DetalleMatriculaRepositorio
{
    public function buscarPorId(int $idDetalle): ?DetalleMatricula
    {
        $sql = "
            SELECT
                id_detalle,
                id_matricula,
                id_seccion,
                estado
            FROM detalle_matricula
            WHERE id_detalle = :id_detalle
            LIMIT 1
        ";

        $resultado = $this->one($sql, [
            ':id_detalle' => $idDetalle
        ]);

        return $resultado
            ? $this->map($resultado)
            : null;
    }

    public function listarPorMatricula(int $idMatricula): array
    {
        $sql = "
            SELECT
                id_detalle,
                id_matricula,
                id_seccion,
                estado
            FROM detalle_matricula
            WHERE id_matricula = :id_matricula
            ORDER BY id_detalle ASC
        ";

        $resultados = $this->all($sql, [
            ':id_matricula' => $idMatricula
        ]);

        return array_map(
            fn(array $fila): DetalleMatricula => $this->map($fila),
            $resultados
        );
    }

    public function guardar(DetalleMatricula $detalleMatricula): void
    {
        $sql = "
            INSERT INTO detalle_matricula (
                id_matricula,
                id_seccion,
                estado
            )
            VALUES (
                :id_matricula,
                :id_seccion,
                :estado
            )
        ";

        $this->exec($sql, [
            ':id_matricula' => $detalleMatricula->getIdMatricula(),
            ':id_seccion' => $detalleMatricula->getIdSeccion(),
            ':estado' => $detalleMatricula->getEstado()
        ]);
    }

    public function actualizar(DetalleMatricula $detalleMatricula): void
    {
        $sql = "
            UPDATE detalle_matricula
            SET
                id_matricula = :id_matricula,
                id_seccion = :id_seccion,
                estado = :estado
            WHERE id_detalle = :id_detalle
        ";

        $this->exec($sql, [
            ':id_detalle' => $detalleMatricula->getIdDetalle(),
            ':id_matricula' => $detalleMatricula->getIdMatricula(),
            ':id_seccion' => $detalleMatricula->getIdSeccion(),
            ':estado' => $detalleMatricula->getEstado()
        ]);
    }

    public function eliminar(int $idDetalle): void
    {
        $sql = "
            DELETE FROM detalle_matricula
            WHERE id_detalle = :id_detalle
        ";

        $this->exec($sql, [
            ':id_detalle' => $idDetalle
        ]);
    }

    private function map(array $fila): DetalleMatricula
    {
        return new DetalleMatricula(
            (int) $fila['id_detalle'],
            (int) $fila['id_matricula'],
            (int) $fila['id_seccion'],
            (string) $fila['estado']
        );
    }
}
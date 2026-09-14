<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\Pago;
use App\Dominio\Repositorios\PagoRepositorio;
use DateTimeImmutable;

final class MySQLPagoRepositorio extends MySQLRepositorioBase implements PagoRepositorio {
    public function buscarPorId(int $idPago): ?Pago
    {
        $sql = "
            SELECT
                id_pago,
                id_estudiante,
                id_concepto,
                fecha_pago,
                monto,
                metodo_pago
            FROM pago
            WHERE id_pago = :id_pago
            LIMIT 1
        ";

        $resultado = $this->one($sql, [
            ':id_pago' => $idPago
        ]);

        return $resultado
            ? $this->map($resultado)
            : null;
    }

    public function buscarPorEstudiante(int $idEstudiante): array
    {
        $sql = "
            SELECT
                id_pago,
                id_estudiante,
                id_concepto,
                fecha_pago,
                monto,
                metodo_pago
            FROM pago
            WHERE id_estudiante = :id_estudiante
            ORDER BY fecha_pago DESC
        ";

        $resultados = $this->all($sql, [
            ':id_estudiante' => $idEstudiante
        ]);

        return array_map(
            fn(array $fila): Pago => $this->map($fila),
            $resultados
        );
    }

    public function guardar(Pago $pago): void
    {
        $sql = "
            INSERT INTO pago (
                id_estudiante,
                id_concepto,
                fecha_pago,
                monto,
                metodo_pago
            )
            VALUES (
                :id_estudiante,
                :id_concepto,
                :fecha_pago,
                :monto,
                :metodo_pago
            )
        ";

        $this->exec($sql, [
            ':id_estudiante' => $pago->getIdEstudiante(),
            ':id_concepto' => $pago->getIdConcepto(),
            ':fecha_pago' => $pago->getFechaPago()->format('Y-m-d H:i:s'),
            ':monto' => $pago->getMonto(),
            ':metodo_pago' => $pago->getMetodoPago()
        ]);
    }

    public function actualizar(Pago $pago): void
    {
        $sql = "
            UPDATE pago
            SET
                id_estudiante = :id_estudiante,
                id_concepto = :id_concepto,
                fecha_pago = :fecha_pago,
                monto = :monto,
                metodo_pago = :metodo_pago
            WHERE id_pago = :id_pago
        ";

        $this->exec($sql, [
            ':id_pago' => $pago->getIdPago(),
            ':id_estudiante' => $pago->getIdEstudiante(),
            ':id_concepto' => $pago->getIdConcepto(),
            ':fecha_pago' => $pago->getFechaPago()->format('Y-m-d H:i:s'),
            ':monto' => $pago->getMonto(),
            ':metodo_pago' => $pago->getMetodoPago()
        ]);
    }

    private function map(array $fila): Pago
    {
        return new Pago(
            (int) $fila['id_pago'],
            (int) $fila['id_estudiante'],
            (int) $fila['id_concepto'],
            new DateTimeImmutable($fila['fecha_pago']),
            (float) $fila['monto'],
            (string) $fila['metodo_pago']
        );
    }
}
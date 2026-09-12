<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\ComprobantePago;
use App\Dominio\Repositorios\ComprobantePagoRepositorio;
use DateTimeImmutable;

final class MySQLComprobantePagoRepositorio
    extends MySQLRepositorioBase
    implements ComprobantePagoRepositorio
{
    public function buscarPorId(
        int $idComprobante
    ): ?ComprobantePago {
        $sql = "
            SELECT
                id_comprobante,
                id_pago,
                tipo,
                numero,
                serie,
                fecha_emision
            FROM comprobante_pago
            WHERE id_comprobante = :id_comprobante
            LIMIT 1
        ";

        $resultado = $this->one(
            $sql,
            [
                ':id_comprobante' => $idComprobante
            ]
        );

        return $resultado
            ? $this->map($resultado)
            : null;
    }

    public function buscarPorPago(
        int $idPago
    ): ?ComprobantePago {
        $sql = "
            SELECT
                id_comprobante,
                id_pago,
                tipo,
                numero,
                serie,
                fecha_emision
            FROM comprobante_pago
            WHERE id_pago = :id_pago
            LIMIT 1
        ";

        $resultado = $this->one(
            $sql,
            [
                ':id_pago' => $idPago
            ]
        );

        return $resultado
            ? $this->map($resultado)
            : null;
    }

    public function guardar(
        ComprobantePago $comprobantePago
    ): void {
        $sql = "
            INSERT INTO comprobante_pago (
                id_pago,
                tipo,
                numero,
                serie,
                fecha_emision
            )
            VALUES (
                :id_pago,
                :tipo,
                :numero,
                :serie,
                :fecha_emision
            )
        ";

        $this->exec(
            $sql,
            [
                ':id_pago' =>
                    $comprobantePago->getIdPago(),

                ':tipo' =>
                    $comprobantePago->getTipo(),

                ':numero' =>
                    $comprobantePago->getNumero(),

                ':serie' =>
                    $comprobantePago->getSerie(),

                ':fecha_emision' =>
                    $comprobantePago->getFechaEmision()
                        ->format('Y-m-d H:i:s')
            ]
        );
    }

    public function actualizar(
        ComprobantePago $comprobantePago
    ): void {
        $sql = "
            UPDATE comprobante_pago
            SET
                id_pago = :id_pago,
                tipo = :tipo,
                numero = :numero,
                serie = :serie,
                fecha_emision = :fecha_emision
            WHERE id_comprobante = :id_comprobante
        ";

        $this->exec(
            $sql,
            [
                ':id_comprobante' =>
                    $comprobantePago->getIdComprobante(),

                ':id_pago' =>
                    $comprobantePago->getIdPago(),

                ':tipo' =>
                    $comprobantePago->getTipo(),

                ':numero' =>
                    $comprobantePago->getNumero(),

                ':serie' =>
                    $comprobantePago->getSerie(),

                ':fecha_emision' =>
                    $comprobantePago->getFechaEmision()
                        ->format('Y-m-d H:i:s')
            ]
        );
    }

    private function map(array $fila): ComprobantePago
    {
        return new ComprobantePago(
            (int) $fila['id_comprobante'],
            (int) $fila['id_pago'],
            (string) $fila['tipo'],
            (string) $fila['numero'],
            (string) $fila['serie'],
            new DateTimeImmutable(
                $fila['fecha_emision']
            )
        );
    }
}
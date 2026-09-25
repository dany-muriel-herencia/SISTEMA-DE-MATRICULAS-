<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\ConceptoPago;
use App\Dominio\Repositorios\ConceptoPagoRepositorio;

final class MySQLConceptoPagoRepositorio
    extends MySQLRepositorioBase
    implements ConceptoPagoRepositorio
{
    public function buscarPorId(
        int $idConcepto
    ): ?ConceptoPago {
        $sql = "
            SELECT
                id_concepto,
                nombre,
                descripcion,
                monto,
                obligatorio
            FROM concepto_pago
            WHERE id_concepto = :id_concepto
            LIMIT 1
        ";

        $resultado = $this->one(
            $sql,
            [
                ':id_concepto' => $idConcepto
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
                id_concepto,
                nombre,
                descripcion,
                monto,
                obligatorio
            FROM concepto_pago
            ORDER BY nombre ASC
        ";

        $resultados = $this->all($sql);

        return array_map(
            fn(array $fila): ConceptoPago =>
                $this->map($fila),
            $resultados
        );
    }

    public function guardar(
        ConceptoPago $conceptoPago
    ): void {
        $sql = "
            INSERT INTO concepto_pago (
                nombre,
                descripcion,
                monto,
                obligatorio
            )
            VALUES (
                :nombre,
                :descripcion,
                :monto,
                :obligatorio
            )
        ";

        $this->exec(
            $sql,
            [
                ':nombre' =>
                    $conceptoPago->getNombre(),

                ':descripcion' =>
                    $conceptoPago->getDescripcion(),

                ':monto' =>
                    $conceptoPago->getMonto(),

                ':obligatorio' =>
                    $conceptoPago->esObligatorio()
            ]
        );
    }

    public function actualizar(
        ConceptoPago $conceptoPago
    ): void {
        $sql = "
            UPDATE concepto_pago
            SET
                nombre = :nombre,
                descripcion = :descripcion,
                monto = :monto,
                obligatorio = :obligatorio
            WHERE id_concepto = :id_concepto
        ";

        $this->exec(
            $sql,
            [
                ':id_concepto' =>
                    $conceptoPago->getIdConcepto(),

                ':nombre' =>
                    $conceptoPago->getNombre(),

                ':descripcion' =>
                    $conceptoPago->getDescripcion(),

                ':monto' =>
                    $conceptoPago->getMonto(),

                ':obligatorio' =>
                    $conceptoPago->esObligatorio()
            ]
        );
    }

    private function map(array $fila): ConceptoPago
    {
        return new ConceptoPago(
            (int) $fila['id_concepto'],
            (string) $fila['nombre'],
            $fila['descripcion'] !== null
                ? $fila['descripcion']
                : null,
            (float) $fila['monto'],
            (bool) $fila['obligatorio']
        );
    }
}
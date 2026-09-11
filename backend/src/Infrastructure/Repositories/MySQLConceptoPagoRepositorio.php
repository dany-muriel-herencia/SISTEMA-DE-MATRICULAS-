<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\ConceptoPago;
use App\Dominio\Repositorios\ConceptoPagoRepositorio;

final class MySQLConceptoPagoRepositorio extends MySQLRepositorioBase implements ConceptoPagoRepositorio
{
    public function buscarPorId(int $idConcepto): ?ConceptoPago { $r = $this->one('SELECT * FROM concepto_pago WHERE id_concepto = :id', [':id' => $idConcepto]); return $r ? $this->map($r) : null; }
    public function listar(): array { return array_map(fn(array $r): ConceptoPago => $this->map($r), $this->all('SELECT * FROM concepto_pago ORDER BY nombre')); }
    public function guardar(ConceptoPago $conceptoPago): void { $this->exec('INSERT INTO concepto_pago (nombre, descripcion, monto, obligatorio) VALUES (:nombre, :descripcion, :monto, :obligatorio)', [':nombre' => $conceptoPago->getNombre(), ':descripcion' => $conceptoPago->getDescripcion(), ':monto' => $conceptoPago->getMonto(), ':obligatorio' => (int) $conceptoPago->esObligatorio()]); }
    public function actualizar(ConceptoPago $conceptoPago): void { $this->exec('UPDATE concepto_pago SET nombre = :nombre, descripcion = :descripcion, monto = :monto, obligatorio = :obligatorio WHERE id_concepto = :id', [':id' => $conceptoPago->getIdConcepto(), ':nombre' => $conceptoPago->getNombre(), ':descripcion' => $conceptoPago->getDescripcion(), ':monto' => $conceptoPago->getMonto(), ':obligatorio' => (int) $conceptoPago->esObligatorio()]); }
    private function map(array $r): ConceptoPago { return new ConceptoPago((int) $r['id_concepto'], (string) $r['nombre'], (string) ($r['descripcion'] ?? ''), (float) $r['monto'], (bool) $r['obligatorio']); }
}

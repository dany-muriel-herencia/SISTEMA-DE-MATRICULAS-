<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\ComprobantePago;
use App\Dominio\Repositorios\ComprobantePagoRepositorio;
use DateTimeImmutable;

final class MySQLComprobantePagoRepositorio extends MySQLRepositorioBase implements ComprobantePagoRepositorio
{
    public function buscarPorId(int $id): ?ComprobantePago { $r = $this->one('SELECT * FROM comprobante_pago WHERE id_comprobante = :id', [':id' => $id]); return $r ? $this->map($r) : null; }
    public function buscarPorPago(int $idPago): ?ComprobantePago { $r = $this->one('SELECT * FROM comprobante_pago WHERE id_pago = :id', [':id' => $idPago]); return $r ? $this->map($r) : null; }
    public function guardar(ComprobantePago $c): void { $this->unsupported('ComprobantePago requiere id_pago, pero la entidad no lo expone.'); }
    public function actualizar(ComprobantePago $c): void { $this->exec('UPDATE comprobante_pago SET tipo = :tipo, numero = :numero, serie = :serie, fecha_emision = :fecha WHERE id_comprobante = :id', [':id' => $c->getIdComprobante(), ':tipo' => $c->getTipo(), ':numero' => $c->getNumero(), ':serie' => $c->getSerie(), ':fecha' => $c->getFechaEmision()->format('Y-m-d H:i:s')]); }
    private function map(array $r): ComprobantePago { return new ComprobantePago((int) $r['id_comprobante'], (string) $r['tipo'], (string) $r['numero'], (string) $r['serie'], new DateTimeImmutable($r['fecha_emision'])); }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\Pago;
use App\Dominio\Repositorios\PagoRepositorio;
use DateTimeImmutable;

final class MySQLPagoRepositorio extends MySQLRepositorioBase implements PagoRepositorio
{
    public function buscarPorId(int $id): ?Pago { $r = $this->one('SELECT * FROM pago WHERE id_pago = :id', [':id' => $id]); return $r ? $this->map($r) : null; }
    public function buscarPorEstudiante(int $idEstudiante): array { return array_map(fn(array $r): Pago => $this->map($r), $this->all('SELECT * FROM pago WHERE id_estudiante = :id ORDER BY fecha_pago DESC', [':id' => $idEstudiante])); }
    public function guardar(Pago $p): void { $this->unsupported('Pago requiere id_estudiante e id_concepto, pero la entidad no los expone.'); }
    public function actualizar(Pago $p): void { $this->exec('UPDATE pago SET fecha_pago = :fecha, monto = :monto, metodo_pago = :metodo WHERE id_pago = :id', [':id' => $p->getIdPago(), ':fecha' => $p->getFechaPago()->format('Y-m-d H:i:s'), ':monto' => $p->getMonto(), ':metodo' => $p->getMetodoPago()]); }
    private function map(array $r): Pago { return new Pago((int) $r['id_pago'], new DateTimeImmutable($r['fecha_pago']), (float) $r['monto'], (string) $r['metodo_pago']); }
}

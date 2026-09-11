<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\Sesion;
use App\Dominio\Repositorios\SesionRepositorio;
use DateTimeImmutable;

final class MySQLSesionRepositorio extends MySQLRepositorioBase implements SesionRepositorio
{
    public function buscarPorId(int $id): ?Sesion { $r = $this->one('SELECT * FROM sesion WHERE id_sesion = :id', [':id' => $id]); return $r ? $this->map($r) : null; }
    public function buscarPorToken(string $token): ?Sesion { $r = $this->one('SELECT * FROM sesion WHERE token = :token', [':token' => $token]); return $r ? $this->map($r) : null; }
    public function listarPorUsuario(int $idUsuario): array { return array_map(fn(array $r): Sesion => $this->map($r), $this->all('SELECT * FROM sesion WHERE id_usuario = :id ORDER BY fecha_inicio DESC', [':id' => $idUsuario])); }
    public function guardar(Sesion $s): void { $this->unsupported('Sesion requiere id_usuario, pero la entidad no lo expone.'); }
    public function actualizar(Sesion $s): void { $this->exec('UPDATE sesion SET token = :token, fecha_inicio = :inicio, fecha_fin = :fin, ip = :ip, activa = :activa WHERE id_sesion = :id', [':id' => $s->getIdSesion(), ':token' => $s->getToken(), ':inicio' => $s->getFechaInicio()->format('Y-m-d H:i:s'), ':fin' => $s->getFechaFin()?->format('Y-m-d H:i:s'), ':ip' => $s->getIp(), ':activa' => (int) $s->getActiva()]); }
    public function eliminar(int $id): void { $this->exec('DELETE FROM sesion WHERE id_sesion = :id', [':id' => $id]); }
    private function map(array $r): Sesion { return new Sesion((int) $r['id_sesion'], (string) $r['token'], new DateTimeImmutable($r['fecha_inicio']), isset($r['fecha_fin']) ? new DateTimeImmutable($r['fecha_fin']) : null, (string) ($r['ip'] ?? ''), (bool) $r['activa']); }
}

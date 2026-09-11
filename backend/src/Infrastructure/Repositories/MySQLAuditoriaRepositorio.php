<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\Auditoria;
use App\Dominio\Repositorios\AuditoriaRepositorio;
use DateTimeImmutable;

final class MySQLAuditoriaRepositorio extends MySQLRepositorioBase implements AuditoriaRepositorio
{
    public function buscarPorId(int $id): ?Auditoria { $r = $this->one('SELECT * FROM auditoria WHERE id_auditoria = :id', [':id' => $id]); return $r ? $this->map($r) : null; }
    public function listar(): array { return array_map(fn(array $r): Auditoria => $this->map($r), $this->all('SELECT * FROM auditoria ORDER BY fecha_hora DESC')); }
    public function listarPorUsuario(int $idUsuario): array { return array_map(fn(array $r): Auditoria => $this->map($r), $this->all('SELECT * FROM auditoria WHERE id_usuario = :id ORDER BY fecha_hora DESC', [':id' => $idUsuario])); }
    public function guardar(Auditoria $a): void { $this->unsupported('Auditoria requiere id_usuario, pero la entidad no lo expone.'); }
    private function map(array $r): Auditoria { return new Auditoria((int) $r['id_auditoria'], (string) $r['accion'], (string) $r['tabla_afectada'], new DateTimeImmutable($r['fecha_hora']), $r['datos_anteriores'] ?? null, $r['datos_nuevos'] ?? null, (string) ($r['ip'] ?? '')); }
}

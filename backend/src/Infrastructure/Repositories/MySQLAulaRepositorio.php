<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\Aula;
use App\Dominio\Repositorios\AulaRepositorio;

final class MySQLAulaRepositorio extends MySQLRepositorioBase implements AulaRepositorio
{
    public function buscarPorId(int $id): ?Aula { $r = $this->one('SELECT * FROM aula WHERE id_aula = :id', [':id' => $id]); return $r ? $this->map($r) : null; }
    public function listar(): array { return array_map(fn(array $r): Aula => $this->map($r), $this->all('SELECT * FROM aula ORDER BY nombre')); }
    public function listarDisponibles(): array { return array_map(fn(array $r): Aula => $this->map($r), $this->all('SELECT * FROM aula WHERE disponible = 1 AND estado = 1 ORDER BY nombre')); }
    public function guardar(Aula $a): void { $this->exec('INSERT INTO aula (nombre, ubicacion, capacidad, tipo, disponible, estado) VALUES (:nombre, :ubicacion, :capacidad, :tipo, :disponible, :estado)', [':nombre' => $a->getNombre(), ':ubicacion' => $a->getUbicacion(), ':capacidad' => $a->getCapacidad(), ':tipo' => $a->getTipo(), ':disponible' => (int) $a->getDisponible(), ':estado' => (int) $a->getEstado()]); }
    public function actualizar(Aula $a): void { $this->exec('UPDATE aula SET nombre = :nombre, ubicacion = :ubicacion, capacidad = :capacidad, tipo = :tipo, disponible = :disponible, estado = :estado WHERE id_aula = :id', [':id' => $a->getIdAula(), ':nombre' => $a->getNombre(), ':ubicacion' => $a->getUbicacion(), ':capacidad' => $a->getCapacidad(), ':tipo' => $a->getTipo(), ':disponible' => (int) $a->getDisponible(), ':estado' => (int) $a->getEstado()]); }
    private function map(array $r): Aula { return new Aula((int) $r['id_aula'], (string) $r['nombre'], (string) ($r['ubicacion'] ?? ''), (int) $r['capacidad'], (string) $r['tipo'], (bool) $r['disponible'], (bool) $r['estado']); }
}

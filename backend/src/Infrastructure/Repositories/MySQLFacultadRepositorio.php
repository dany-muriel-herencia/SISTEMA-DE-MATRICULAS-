<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\Facultad;
use App\Dominio\Repositorios\FacultadRepositorio;

final class MySQLFacultadRepositorio extends MySQLRepositorioBase implements FacultadRepositorio
{
    public function buscarPorId(int $id): ?Facultad { $r = $this->one('SELECT * FROM facultad WHERE id_facultad = :id', [':id' => $id]); return $r ? $this->map($r) : null; }
    public function listar(): array { return array_map(fn(array $r): Facultad => $this->map($r), $this->all('SELECT * FROM facultad ORDER BY nombre')); }
    public function guardar(Facultad $f): void { $this->exec('INSERT INTO facultad (nombre, descripcion, decano) VALUES (:nombre, :descripcion, :decano)', [':nombre' => $f->getNombre(), ':descripcion' => $f->getDescripcion(), ':decano' => $f->getDecano()]); }
    public function actualizar(Facultad $f): void { $this->exec('UPDATE facultad SET nombre = :nombre, descripcion = :descripcion, decano = :decano WHERE id_facultad = :id', [':id' => $f->getIdFacultad(), ':nombre' => $f->getNombre(), ':descripcion' => $f->getDescripcion(), ':decano' => $f->getDecano()]); }
    private function map(array $r): Facultad { return new Facultad((int) $r['id_facultad'], (string) $r['nombre'], (string) ($r['descripcion'] ?? ''), (string) ($r['decano'] ?? '')); }
}

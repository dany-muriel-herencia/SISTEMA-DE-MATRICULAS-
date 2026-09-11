<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\Carrera;
use App\Dominio\Repositorios\CarreraRepositorio;

final class MySQLCarreraRepositorio extends MySQLRepositorioBase implements CarreraRepositorio
{
    public function buscarPorId(int $id): ?Carrera { $r = $this->one('SELECT * FROM carrera WHERE id_carrera = :id', [':id' => $id]); return $r ? $this->map($r) : null; }
    public function buscarPorCodigo(string $codigo): ?Carrera { $r = $this->one('SELECT * FROM carrera WHERE codigo = :codigo', [':codigo' => $codigo]); return $r ? $this->map($r) : null; }
    public function listar(): array { return array_map(fn(array $r): Carrera => $this->map($r), $this->all('SELECT * FROM carrera ORDER BY nombre')); }
    public function guardar(Carrera $c): void { $this->unsupported('Carrera requiere id_escuela, pero la entidad no lo expone.'); }
    public function actualizar(Carrera $c): void { $this->exec('UPDATE carrera SET nombre = :nombre, codigo = :codigo, duracion = :duracion, estado = :estado WHERE id_carrera = :id', [':id' => $c->getIdCarrera(), ':nombre' => $c->getNombre(), ':codigo' => $c->getCodigo(), ':duracion' => $c->getDuracion(), ':estado' => (int) $c->getEstado()]); }
    private function map(array $r): Carrera { return new Carrera((int) $r['id_carrera'], (string) $r['nombre'], (string) $r['codigo'], (int) $r['duracion'], (bool) $r['estado']); }
}

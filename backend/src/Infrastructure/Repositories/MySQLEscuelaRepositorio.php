<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\Escuela;
use App\Dominio\Repositorios\EscuelaRepositorio;

final class MySQLEscuelaRepositorio extends MySQLRepositorioBase implements EscuelaRepositorio
{
    public function buscarPorId(int $id): ?Escuela { 
        $r = $this->one('SELECT * FROM escuela WHERE id_escuela = :id', 
        [':id' => $id]); return $r ? $this->map($r) : null; }
    public function listar(): array { 
        return array_map(fn(array $r): Escuela => $this->map($r),
        $this->all('SELECT * FROM escuela ORDER BY nombre')); 
     }

    public function guardar(Escuela $e): void { $this->unsupported('Escuela requiere id_facultad, pero la entidad no lo expone.'); }
    public function actualizar(Escuela $e): void { $this->unsupported('Escuela requiere id_facultad, pero la entidad no lo expone.'); }
    private function map(array $r): Escuela { return new Escuela((int) $r['id_escuela'], (string) $r['nombre'], (string) ($r['descripcion'] ?? ''), (string) ($r['director'] ?? '')); }
}

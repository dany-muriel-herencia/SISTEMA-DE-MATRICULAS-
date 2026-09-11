<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\DetalleMatricula;
use App\Dominio\Repositorios\DetalleMatriculaRepositorio;

final class MySQLDetalleMatriculaRepositorio extends MySQLRepositorioBase implements DetalleMatriculaRepositorio
{
    public function buscarPorId(int $id): ?DetalleMatricula { $r = $this->one('SELECT * FROM detalle_matricula WHERE id_detalle = :id', [':id' => $id]); return $r ? $this->map($r) : null; }
    public function listarPorMatricula(int $idMatricula): array { return array_map(fn(array $r): DetalleMatricula => $this->map($r), $this->all('SELECT * FROM detalle_matricula WHERE id_matricula = :id ORDER BY id_detalle', [':id' => $idMatricula])); }
    public function guardar(DetalleMatricula $d): void { $this->unsupported('DetalleMatricula requiere id_matricula e id_seccion, pero la entidad no los expone.'); }
    public function actualizar(DetalleMatricula $d): void { $this->exec('UPDATE detalle_matricula SET estado = :estado WHERE id_detalle = :id', [':id' => $d->getIdDetalle(), ':estado' => $d->getEstado()]); }
    public function eliminar(int $id): void { $this->exec('DELETE FROM detalle_matricula WHERE id_detalle = :id', [':id' => $id]); }
    private function map(array $r): DetalleMatricula { return new DetalleMatricula((int) $r['id_detalle'], (string) $r['estado']); }
}

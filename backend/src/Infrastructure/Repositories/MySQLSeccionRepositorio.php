<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\Seccion;
use App\Dominio\Repositorios\SeccionRepositorio;

final class MySQLSeccionRepositorio extends MySQLRepositorioBase implements SeccionRepositorio
{
    public function buscarPorId(int $id): ?Seccion { $r = $this->one('SELECT * FROM seccion WHERE id_seccion = :id', [':id' => $id]); return $r ? $this->map($r) : null; }
    public function listarDisponibles(): array { return array_map(fn(array $r): Seccion => $this->map($r), $this->all('SELECT * FROM seccion WHERE vacantes_disponibles > 0 ORDER BY codigo')); }
    public function guardar(Seccion $s): void { $this->unsupported('Seccion requiere id_curso, id_periodo e id_docente, pero la entidad no los expone.'); }
    public function actualizar(Seccion $s): void { $this->exec('UPDATE seccion SET codigo = :codigo, vacantes = :vacantes, vacantes_disponibles = :disponibles WHERE id_seccion = :id', [':id' => $s->getIdSeccion(), ':codigo' => $s->getCodigo(), ':vacantes' => $s->getVacantes(), ':disponibles' => $s->getVacantesDisponibles()]); }
    private function map(array $r): Seccion { return new Seccion((int) $r['id_seccion'], (string) $r['codigo'], (int) $r['vacantes'], (int) $r['vacantes_disponibles'], (string) $r['id_periodo']); }
}

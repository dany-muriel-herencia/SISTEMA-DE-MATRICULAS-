<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\PlanEstudio;
use App\Dominio\Repositorios\PlanEstudioRepositorio;
use DateTimeImmutable;

final class MySQLPlanEstudioRepositorio extends MySQLRepositorioBase implements PlanEstudioRepositorio
{
    public function buscarPorId(int $id): ?PlanEstudio { $r = $this->one('SELECT * FROM plan_estudio WHERE id_plan = :id', [':id' => $id]); return $r ? $this->map($r) : null; }
    public function listar(): array { return array_map(fn(array $r): PlanEstudio => $this->map($r), $this->all('SELECT * FROM plan_estudio ORDER BY nombre')); }
    public function guardar(PlanEstudio $p): void { $this->unsupported('PlanEstudio requiere id_carrera, pero la entidad no lo expone.'); }
    public function actualizar(PlanEstudio $p): void { $this->exec('UPDATE plan_estudio SET nombre = :nombre, fecha_inicio = :inicio, fecha_fin = :fin, estado = :estado WHERE id_plan = :id', [':id' => $p->getIdPlan(), ':nombre' => $p->getNombre(), ':inicio' => $p->getFechaInicio()->format('Y-m-d'), ':fin' => $p->getFechaFin()?->format('Y-m-d'), ':estado' => (int) $p->getEstado()]); }
    private function map(array $r): PlanEstudio { return new PlanEstudio((int) $r['id_plan'], (string) $r['nombre'], new DateTimeImmutable($r['fecha_inicio']), isset($r['fecha_fin']) ? new DateTimeImmutable($r['fecha_fin']) : null, (bool) $r['estado']); }
}

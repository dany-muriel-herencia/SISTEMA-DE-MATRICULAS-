<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\PeriodoAcademico;
use App\Dominio\Repositorios\PeriodoAcademicoRepositorio;
use DateTimeImmutable;

final class MySQLPeriodoAcademicoRepositorio extends MySQLRepositorioBase implements PeriodoAcademicoRepositorio
{
    public function buscarPorId(int $idPeriodo): ?PeriodoAcademico { $r = $this->one('SELECT * FROM periodo_academico WHERE id_periodo = :id', [':id' => $idPeriodo]); return $r ? $this->map($r) : null; }
    public function obtenerPeriodoActivo(): ?PeriodoAcademico { $r = $this->one('SELECT * FROM periodo_academico WHERE estado = :estado ORDER BY id_periodo DESC LIMIT 1', [':estado' => 'ACTIVO']); return $r ? $this->map($r) : null; }
    public function guardar(PeriodoAcademico $p): void { $this->exec('INSERT INTO periodo_academico (nombre, fecha_inicio, fecha_fin, fecha_matricula_inicio, fecha_matricula_fin, estado) VALUES (:nombre, :inicio, :fin, :mi, :mf, :estado)', [':nombre' => $p->getNombre(), ':inicio' => $p->getFechaInicio()->format('Y-m-d'), ':fin' => $p->getFechaFin()->format('Y-m-d'), ':mi' => $p->getFechaMatriculaInicio()->format('Y-m-d'), ':mf' => $p->getFechaMatriculaFin()->format('Y-m-d'), ':estado' => $p->getEstado()]); }
    public function actualizar(PeriodoAcademico $p): void { $this->exec('UPDATE periodo_academico SET nombre = :nombre, fecha_inicio = :inicio, fecha_fin = :fin, fecha_matricula_inicio = :mi, fecha_matricula_fin = :mf, estado = :estado WHERE id_periodo = :id', [':id' => $p->getIdPeriodo(), ':nombre' => $p->getNombre(), ':inicio' => $p->getFechaInicio()->format('Y-m-d'), ':fin' => $p->getFechaFin()->format('Y-m-d'), ':mi' => $p->getFechaMatriculaInicio()->format('Y-m-d'), ':mf' => $p->getFechaMatriculaFin()->format('Y-m-d'), ':estado' => $p->getEstado()]); }
    private function map(array $r): PeriodoAcademico { return new PeriodoAcademico((int) $r['id_periodo'], (string) $r['nombre'], new DateTimeImmutable($r['fecha_inicio']), new DateTimeImmutable($r['fecha_fin']), new DateTimeImmutable($r['fecha_matricula_inicio']), new DateTimeImmutable($r['fecha_matricula_fin']), (string) $r['estado']); }
}

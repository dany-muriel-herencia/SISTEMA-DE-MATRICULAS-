<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\Matricula;
use App\Dominio\Repositorios\MatriculaRepositorio;
use DateTimeImmutable;

final class MySQLMatriculaRepositorio extends MySQLRepositorioBase implements MatriculaRepositorio
{
    public function buscarPorId(int $id): ?Matricula { $r = $this->one('SELECT * FROM matricula WHERE id_matricula = :id', [':id' => $id]); return $r ? $this->map($r) : null; }
    public function buscarPorEstudiante(int $idEstudiante): array { return $this->buscarLista('SELECT * FROM matricula WHERE id_estudiante = :id ORDER BY id_matricula DESC', $idEstudiante); }
    public function buscarPorPeriodo(int $idPeriodo): array { return $this->buscarLista('SELECT * FROM matricula WHERE id_periodo = :id ORDER BY id_matricula DESC', $idPeriodo); }
    public function guardar(Matricula $m): void { $this->unsupported('Matricula requiere id_estudiante e id_periodo, pero la entidad no los expone.'); }
    public function actualizar(Matricula $m): void { $this->exec('UPDATE matricula SET fecha_matricula = :fecha, estado = :estado, total_creditos = :creditos WHERE id_matricula = :id', [':id' => $m->getIdMatricula(), ':fecha' => $m->getFechaMatricula()->format('Y-m-d'), ':estado' => $m->getEstado(), ':creditos' => $m->getTotalCreditos()]); }
    private function buscarLista(string $sql, int $id): array { return array_map(fn(array $r): Matricula => $this->map($r), $this->all($sql, [':id' => $id])); }
    private function map(array $r): Matricula { return new Matricula((int) $r['id_matricula'], new DateTimeImmutable($r['fecha_matricula']), (string) $r['estado'], (int) $r['total_creditos']); }
}

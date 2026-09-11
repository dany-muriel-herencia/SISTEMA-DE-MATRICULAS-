<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\Curriculum;
use App\Dominio\Repositorios\CurriculumRepositorio;

final class MySQLCurriculumRepositorio extends MySQLRepositorioBase implements CurriculumRepositorio
{
    public function buscarPorId(int $id): ?Curriculum { $r = $this->one('SELECT * FROM curriculum WHERE id_curriculum = :id', [':id' => $id]); return $r ? $this->map($r) : null; }
    public function listar(): array { return array_map(fn(array $r): Curriculum => $this->map($r), $this->all('SELECT * FROM curriculum ORDER BY id_curriculum')); }
    public function guardar(Curriculum $c): void { $this->unsupported('Curriculum requiere id_plan e id_curso, pero la entidad no los expone.'); }
    public function actualizar(Curriculum $c): void { $this->exec('UPDATE curriculum SET ciclo = :ciclo, obligatorio = :obligatorio WHERE id_curriculum = :id', [':id' => $c->getIdCurriculum(), ':ciclo' => $c->getCiclo(), ':obligatorio' => (int) $c->esObligatorio()]); }
    private function map(array $r): Curriculum { return new Curriculum((int) $r['id_curriculum'], (string) $r['ciclo'], (bool) $r['obligatorio']); }
}

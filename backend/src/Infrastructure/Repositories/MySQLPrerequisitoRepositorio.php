<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\Prerequisito;
use App\Dominio\Repositorios\PrerequisitoRepositorio;

final class MySQLPrerequisitoRepositorio extends MySQLRepositorioBase implements PrerequisitoRepositorio
{
    public function buscarPorId(int $id): ?Prerequisito { $r = $this->one('SELECT * FROM prerequisito WHERE id_prerequisito = :id', [':id' => $id]); return $r ? new Prerequisito((int) $r['id_prerequisito']) : null; }
    public function listar(): array { return array_map(fn(array $r): Prerequisito => new Prerequisito((int) $r['id_prerequisito']), $this->all('SELECT id_prerequisito FROM prerequisito ORDER BY id_prerequisito')); }
    public function guardar(Prerequisito $p): void { $this->unsupported('Prerequisito requiere id_curso e id_curso_requerido, pero la entidad no los expone.'); }
    public function eliminar(int $id): void { $this->exec('DELETE FROM prerequisito WHERE id_prerequisito = :id', [':id' => $id]); }
}

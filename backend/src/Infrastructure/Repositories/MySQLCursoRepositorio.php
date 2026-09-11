<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\Curso;
use App\Dominio\Repositorios\CursoRepositorio;

final class MySQLCursoRepositorio extends MySQLRepositorioBase implements CursoRepositorio
{
    public function buscarPorId(int $idCurso): ?Curso { $r = $this->one('SELECT * FROM curso WHERE id_curso = :id', [':id' => $idCurso]); return $r ? $this->map($r) : null; }
    public function buscarPorCodigo(string $codigo): ?Curso { $r = $this->one('SELECT * FROM curso WHERE codigo = :codigo', [':codigo' => $codigo]); return $r ? $this->map($r) : null; }
    public function listarActivos(): array { return array_map(fn(array $r): Curso => $this->map($r), $this->all('SELECT * FROM curso WHERE estado = 1 ORDER BY nombre')); }
    public function guardar(Curso $curso): void { $this->exec('INSERT INTO curso (nombre, codigo, creditos, horas_teoria, horas_practica, ciclo, estado) VALUES (:nombre, :codigo, :creditos, :teoria, :practica, :ciclo, :estado)', [':nombre' => $curso->getNombre(), ':codigo' => $curso->getCodigo(), ':creditos' => $curso->getCreditos(), ':teoria' => $curso->getHorasTeoria(), ':practica' => $curso->getHorasPractica(), ':ciclo' => $curso->getCiclo(), ':estado' => (int) $curso->getEstado()]); }
    public function actualizar(Curso $curso): void { $this->exec('UPDATE curso SET nombre = :nombre, codigo = :codigo, creditos = :creditos, horas_teoria = :teoria, horas_practica = :practica, ciclo = :ciclo, estado = :estado WHERE id_curso = :id', [':id' => $curso->getIdCurso(), ':nombre' => $curso->getNombre(), ':codigo' => $curso->getCodigo(), ':creditos' => $curso->getCreditos(), ':teoria' => $curso->getHorasTeoria(), ':practica' => $curso->getHorasPractica(), ':ciclo' => $curso->getCiclo(), ':estado' => (int) $curso->getEstado()]); }
    private function map(array $r): Curso { return new Curso((int) $r['id_curso'], (string) $r['nombre'], (string) $r['codigo'], (int) $r['creditos'], (int) $r['horas_teoria'], (int) $r['horas_practica'], (string) $r['ciclo'], (bool) $r['estado']); }
}

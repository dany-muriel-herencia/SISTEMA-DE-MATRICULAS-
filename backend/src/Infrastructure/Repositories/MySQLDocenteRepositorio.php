<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\Docente;
use App\Dominio\Repositorios\DocenteRepositorio;
use DateTimeImmutable;

final class MySQLDocenteRepositorio extends MySQLRepositorioBase implements DocenteRepositorio
{
    public function buscarPorId(int $idUsuario): ?Docente { $r = $this->one('SELECT u.*, d.codigo, d.especialidad, d.grado_academico FROM usuario u JOIN docente d ON d.id_usuario = u.id_usuario WHERE d.id_usuario = :id', [':id' => $idUsuario]); return $r ? $this->map($r) : null; }
    public function buscarPorCodigo(string $codigo): ?Docente { $r = $this->one('SELECT u.*, d.codigo, d.especialidad, d.grado_academico FROM usuario u JOIN docente d ON d.id_usuario = u.id_usuario WHERE d.codigo = :codigo', [':codigo' => $codigo]); return $r ? $this->map($r) : null; }
    public function guardar(Docente $d): void { $this->unsupported('Docente requiere crear primero su Usuario y conocer el id_usuario.'); }
    public function actualizar(Docente $d): void { $this->exec('UPDATE docente SET codigo = :codigo, especialidad = :especialidad, grado_academico = :grado WHERE id_usuario = :id', [':id' => $d->getIdUsuario(), ':codigo' => $d->getCodigo(), ':especialidad' => $d->getEspecialidad(), ':grado' => $d->getGradoAcademico()]); }
    private function map(array $r): Docente { return new Docente((int) $r['id_usuario'], (string) $r['nombre'], (string) $r['email'], (string) $r['contrasenha'], (string) $r['rol'], (bool) $r['estado'], new DateTimeImmutable($r['fecha_creacion']), (string) $r['codigo'], (string) $r['especialidad'], (string) $r['grado_academico']); }
}

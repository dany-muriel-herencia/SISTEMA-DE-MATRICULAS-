<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\Estudiante;
use App\Dominio\Repositorios\EstudianteRepositorio;
use DateTimeImmutable;

final class MySQLEstudianteRepositorio extends MySQLRepositorioBase implements EstudianteRepositorio
{
    public function buscarPorId(int $idUsuario): ?Estudiante { $r = $this->one('SELECT u.*, e.codigo_universitario, e.dni, e.fecha_nacimiento, e.fecha_ingreso, e.promedio_academico FROM usuario u JOIN estudiante e ON e.id_usuario = u.id_usuario WHERE e.id_usuario = :id', [':id' => $idUsuario]); return $r ? $this->map($r) : null; }
    public function buscarPorCodigo(string $codigo): ?Estudiante { $r = $this->one('SELECT u.*, e.codigo_universitario, e.dni, e.fecha_nacimiento, e.fecha_ingreso, e.promedio_academico FROM usuario u JOIN estudiante e ON e.id_usuario = u.id_usuario WHERE e.codigo_universitario = :codigo', [':codigo' => $codigo]); return $r ? $this->map($r) : null; }
    public function buscarPorDni(string $dni): ?Estudiante { $r = $this->one('SELECT u.*, e.codigo_universitario, e.dni, e.fecha_nacimiento, e.fecha_ingreso, e.promedio_academico FROM usuario u JOIN estudiante e ON e.id_usuario = u.id_usuario WHERE e.dni = :dni', [':dni' => $dni]); return $r ? $this->map($r) : null; }
    public function guardar(Estudiante $e): void { $this->unsupported('Estudiante requiere crear primero su Usuario y conocer el id_usuario.'); }
    public function actualizar(Estudiante $e): void { $this->exec('UPDATE estudiante SET codigo_universitario = :codigo, dni = :dni, fecha_nacimiento = :nacimiento, fecha_ingreso = :ingreso, promedio_academico = :promedio WHERE id_usuario = :id', [':id' => $e->getIdUsuario(), ':codigo' => $e->getCodigoUniversitario(), ':dni' => $e->getDni(), ':nacimiento' => $e->getFechaNacimiento()->format('Y-m-d'), ':ingreso' => $e->getFechaIngreso()->format('Y-m-d'), ':promedio' => $e->getPromedioAcademico()]); }
    private function map(array $r): Estudiante { return new Estudiante((int) $r['id_usuario'], (string) $r['nombre'], (string) $r['email'], (string) $r['contrasenha'], (string) $r['rol'], (bool) $r['estado'], new DateTimeImmutable($r['fecha_creacion']), (string) $r['codigo_universitario'], (string) $r['dni'], new DateTimeImmutable($r['fecha_nacimiento']), new DateTimeImmutable($r['fecha_ingreso']), (float) $r['promedio_academico']); }
}

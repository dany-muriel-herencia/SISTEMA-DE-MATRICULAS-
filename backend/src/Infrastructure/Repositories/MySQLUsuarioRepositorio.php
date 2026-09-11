<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\Usuario;
use App\Dominio\Repositorios\UsuarioRepositorio;
use DateTimeImmutable;

final class MySQLUsuarioRepositorio extends MySQLRepositorioBase implements UsuarioRepositorio
{
    public function buscarPorId(int $idUsuario): ?Usuario { $r = $this->one('SELECT * FROM usuario WHERE id_usuario = :id', [':id' => $idUsuario]); return $r ? $this->map($r) : null; }
    public function buscarPorEmail(string $email): ?Usuario { $r = $this->one('SELECT * FROM usuario WHERE email = :email', [':email' => $email]); return $r ? $this->map($r) : null; }
    public function guardar(Usuario $u): void { $this->exec('INSERT INTO usuario (nombre, email, contrasenha, rol, estado, fecha_creacion) VALUES (:nombre, :email, :pass, :rol, :estado, :fecha)', [':nombre' => $u->getNombre(), ':email' => $u->getEmail(), ':pass' => $u->getContrasenha(), ':rol' => $u->getRol(), ':estado' => (int) $u->getEstado(), ':fecha' => $u->getFechaCreacion()->format('Y-m-d H:i:s')]); }
    public function actualizar(Usuario $u): void { $this->exec('UPDATE usuario SET nombre = :nombre, email = :email, contrasenha = :pass, rol = :rol, estado = :estado WHERE id_usuario = :id', [':id' => $u->getIdUsuario(), ':nombre' => $u->getNombre(), ':email' => $u->getEmail(), ':pass' => $u->getContrasenha(), ':rol' => $u->getRol(), ':estado' => (int) $u->getEstado()]); }
    public function eliminar(int $idUsuario): void { $this->exec('DELETE FROM usuario WHERE id_usuario = :id', [':id' => $idUsuario]); }
    protected function map(array $r): Usuario { return new Usuario((int) $r['id_usuario'], (string) $r['nombre'], (string) $r['email'], (string) $r['contrasenha'], (string) $r['rol'], (bool) $r['estado'], new DateTimeImmutable((string) $r['fecha_creacion'])); }
}

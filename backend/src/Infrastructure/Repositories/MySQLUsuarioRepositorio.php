<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\Usuario;
use App\Dominio\Repositorios\UsuarioRepositorio;
use DateTimeImmutable;
use PDO;

final class MySQLUsuarioRepositorio extends MySQLRepositorioBase implements UsuarioRepositorio
{
    public function buscarPorId(int $idUsuario): ?Usuario
    {
        $sql = "
            SELECT
                id_usuario,
                nombre,
                email,
                contrasenha,
                rol,
                estado,
                fecha_creacion
            FROM usuario
            WHERE id_usuario = :id_usuario
            LIMIT 1
        ";

        $r = $this->one($sql, [
            ':id_usuario' => $idUsuario
        ]);

        return $r ? $this->map($r) : null;
    }

    public function buscarPorEmail(string $email): ?Usuario
    {
        $sql = "
            SELECT
                id_usuario,
                nombre,
                email,
                contrasenha,
                rol,
                estado,
                fecha_creacion
            FROM usuario
            WHERE email = :email
            LIMIT 1
        ";

        $r = $this->one($sql, [
            ':email' => $email
        ]);

        return $r ? $this->map($r) : null;
    }

    public function guardar(Usuario $usuario): void
    {
        if ($usuario->getIdUsuario() > 0) {
            $sql = "
                INSERT INTO usuario (
                    id_usuario,
                    nombre,
                    email,
                    contrasenha,
                    rol,
                    estado,
                    fecha_creacion
                )
                VALUES (
                    :id_usuario,
                    :nombre,
                    :email,
                    :contrasenha,
                    :rol,
                    :estado,
                    :fecha_creacion
                )
            ";

            $this->exec($sql, [
                ':id_usuario' => $usuario->getIdUsuario(),
                ':nombre' => $usuario->getNombre(),
                ':email' => $usuario->getEmail(),
                ':contrasenha' => $usuario->getContrasenha(),
                ':rol' => $usuario->getRol(),
                ':estado' => $usuario->getEstado() ? 1 : 0,
                ':fecha_creacion' => $usuario->getFechaCreacion()->format('Y-m-d H:i:s')
            ]);
        } else {
            $sql = "
                INSERT INTO usuario (
                    nombre,
                    email,
                    contrasenha,
                    rol,
                    estado,
                    fecha_creacion
                )
                VALUES (
                    :nombre,
                    :email,
                    :contrasenha,
                    :rol,
                    :estado,
                    :fecha_creacion
                )
            ";

            $this->exec($sql, [
                ':nombre' => $usuario->getNombre(),
                ':email' => $usuario->getEmail(),
                ':contrasenha' => $usuario->getContrasenha(),
                ':rol' => $usuario->getRol(),
                ':estado' => $usuario->getEstado() ? 1 : 0,
                ':fecha_creacion' => $usuario->getFechaCreacion()->format('Y-m-d H:i:s')
            ]);
        }
    }

    public function actualizar(Usuario $usuario): void
    {
        $sql = "
            UPDATE usuario
            SET
                nombre = :nombre,
                email = :email,
                contrasenha = :contrasenha,
                rol = :rol,
                estado = :estado
            WHERE id_usuario = :id_usuario
        ";

        $this->exec($sql, [
            ':id_usuario' => $usuario->getIdUsuario(),
            ':nombre' => $usuario->getNombre(),
            ':email' => $usuario->getEmail(),
            ':contrasenha' => $usuario->getContrasenha(),
            ':rol' => $usuario->getRol(),
            ':estado' => $usuario->getEstado() ? 1 : 0
        ]);
    }

    public function eliminar(int $idUsuario): void
    {
        $sql = "
            DELETE FROM usuario
            WHERE id_usuario = :id_usuario
        ";

        $this->exec($sql, [
            ':id_usuario' => $idUsuario
        ]);
    }

    public function listar(int $limit = 50, int $offset = 0): array
    {
        $sql = "
            SELECT
                id_usuario,
                nombre,
                email,
                contrasenha,
                rol,
                estado,
                fecha_creacion
            FROM usuario
            ORDER BY id_usuario DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();

        return array_map(fn(array $r): Usuario => $this->map($r), $rows);
    }

    private function map(array $r): Usuario
    {
        return new Usuario(
            (int) $r['id_usuario'],
            (string) $r['nombre'],
            (string) $r['email'],
            (string) $r['contrasenha'],
            (string) $r['rol'],
            (bool) $r['estado'],
            new DateTimeImmutable((string) $r['fecha_creacion'])
        );
    }
}

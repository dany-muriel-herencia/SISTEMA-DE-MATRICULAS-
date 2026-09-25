<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\Sesion;
use App\Dominio\Repositorios\SesionRepositorio;
use DateTimeImmutable;
use PDO;

class MySQLSesionRepositorio extends MySQLRepositorioBase implements SesionRepositorio
{
    public function buscarPorId(int $id_sesion): ?Sesion
    {
        $sql = "
            SELECT
                id_sesion,
                id_usuario,
                token,
                fecha_inicio,
                fecha_fin,
                ip,
                activa
            FROM sesion
            WHERE id_sesion = :id_sesion
        ";

        $row = $this->one($sql, [
            'id_sesion' => $id_sesion
        ]);

        if ($row === null) {
            return null;
        }

        return $this->mapearSesion($row);
    }

    public function buscarPorToken(string $token): ?Sesion
    {
        $sql = "
            SELECT
                id_sesion,
                id_usuario,
                token,
                fecha_inicio,
                fecha_fin,
                ip,
                activa
            FROM sesion
            WHERE token = :token
        ";

        $row = $this->one($sql, [
            'token' => $token
        ]);

        if ($row === null) {
            return null;
        }

        return $this->mapearSesion($row);
    }

    public function listarPorUsuario(int $id_usuario): array
    {
        $sql = "
            SELECT
                id_sesion,
                id_usuario,
                token,
                fecha_inicio,
                fecha_fin,
                ip,
                activa
            FROM sesion
            WHERE id_usuario = :id_usuario
            ORDER BY fecha_inicio DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id_usuario' => $id_usuario
        ]);

        $sesiones = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sesiones[] = $this->mapearSesion($row);
        }

        return $sesiones;
    }

    public function guardar(Sesion $sesion): void
    {
        $sql = "
            INSERT INTO sesion (
                id_usuario,
                token,
                fecha_inicio,
                fecha_fin,
                ip,
                activa
            )
            VALUES (
                :id_usuario,
                :token,
                :fecha_inicio,
                :fecha_fin,
                :ip,
                :activa
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id_usuario' => $sesion->getIdUsuario(),
            'token' => $sesion->getToken(),
            'fecha_inicio' => $sesion->getFechaInicio()->format('Y-m-d H:i:s'),
            'fecha_fin' => $sesion->getFechaFin()?->format('Y-m-d H:i:s'),
            'ip' => $sesion->getIp(),
            'activa' => $sesion->getActiva() ? 1 : 0
        ]);
        $sesion->setIdSesion($this->generatedId());
    }

    public function actualizar(Sesion $sesion): void
    {
        $sql = "
            UPDATE sesion
            SET
                id_usuario = :id_usuario,
                token = :token,
                fecha_inicio = :fecha_inicio,
                fecha_fin = :fecha_fin,
                ip = :ip,
                activa = :activa
            WHERE id_sesion = :id_sesion
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id_sesion' => $sesion->getIdSesion(),
            'id_usuario' => $sesion->getIdUsuario(),
            'token' => $sesion->getToken(),
            'fecha_inicio' => $sesion->getFechaInicio()->format('Y-m-d H:i:s'),
            'fecha_fin' => $sesion->getFechaFin()?->format('Y-m-d H:i:s'),
            'ip' => $sesion->getIp(),
            'activa' => $sesion->getActiva() ? 1 : 0
        ]);
    }

    public function eliminar(int $id_sesion): void
    {
        $sql = "
            DELETE FROM sesion
            WHERE id_sesion = :id_sesion
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id_sesion' => $id_sesion
        ]);
    }

    private function mapearSesion(array $row): Sesion
    {
        return new Sesion(
            (int) $row['id_sesion'],
            (int) $row['id_usuario'],
            $row['token'],
            new DateTimeImmutable($row['fecha_inicio']),
            $row['fecha_fin'] !== null
                ? new DateTimeImmutable($row['fecha_fin'])
                : null,
            $row['ip'],
            (bool) $row['activa']
        );
    }
}
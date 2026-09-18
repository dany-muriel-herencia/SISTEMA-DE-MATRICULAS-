<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\Sesion;
use App\Dominio\Repositorios\SesionRepositorio;
use DateTimeImmutable;
use PDO;

class MySQLSesionRepositorio extends MySQLRepositorioBase implements SesionRepositorio
{
    public function buscarPorId(int $idSesion): ?Sesion
    {
        $sql = "
            SELECT
                idSesion,
                idUsuario,
                token,
                fechaInicio,
                fechaFin,
                ip,
                activa
            FROM sesion
            WHERE idSesion = :idSesion
        ";

        $row = $this->one($sql, [
            'idSesion' => $idSesion
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
                idSesion,
                idUsuario,
                token,
                fechaInicio,
                fechaFin,
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

    public function listarPorUsuario(int $idUsuario): array
    {
        $sql = "
            SELECT
                idSesion,
                idUsuario,
                token,
                fechaInicio,
                fechaFin,
                ip,
                activa
            FROM sesion
            WHERE idUsuario = :idUsuario
            ORDER BY fechaInicio DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'idUsuario' => $idUsuario
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
                idSesion,
                idUsuario,
                token,
                fechaInicio,
                fechaFin,
                ip,
                activa
            )
            VALUES (
                :idSesion,
                :idUsuario,
                :token,
                :fechaInicio,
                :fechaFin,
                :ip,
                :activa
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'idSesion' => $sesion->getIdSesion(),
            'idUsuario' => $sesion->getIdUsuario(),
            'token' => $sesion->getToken(),
            'fechaInicio' => $sesion->getFechaInicio()->format('Y-m-d H:i:s'),
            'fechaFin' => $sesion->getFechaFin()?->format('Y-m-d H:i:s'),
            'ip' => $sesion->getIp(),
            'activa' => $sesion->getActiva() ? 1 : 0
        ]);
    }

    public function actualizar(Sesion $sesion): void
    {
        $sql = "
            UPDATE sesion
            SET
                idUsuario = :idUsuario,
                token = :token,
                fechaInicio = :fechaInicio,
                fechaFin = :fechaFin,
                ip = :ip,
                activa = :activa
            WHERE idSesion = :idSesion
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'idSesion' => $sesion->getIdSesion(),
            'idUsuario' => $sesion->getIdUsuario(),
            'token' => $sesion->getToken(),
            'fechaInicio' => $sesion->getFechaInicio()->format('Y-m-d H:i:s'),
            'fechaFin' => $sesion->getFechaFin()?->format('Y-m-d H:i:s'),
            'ip' => $sesion->getIp(),
            'activa' => $sesion->getActiva() ? 1 : 0
        ]);
    }

    public function eliminar(int $idSesion): void
    {
        $sql = "
            DELETE FROM sesion
            WHERE idSesion = :idSesion
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'idSesion' => $idSesion
        ]);
    }

    private function mapearSesion(array $row): Sesion
    {
        return new Sesion(
            (int) $row['idSesion'],
            (int) $row['idUsuario'],
            $row['token'],
            new DateTimeImmutable($row['fechaInicio']),
            $row['fechaFin'] !== null
                ? new DateTimeImmutable($row['fechaFin'])
                : null,
            $row['ip'],
            (bool) $row['activa']
        );
    }
}
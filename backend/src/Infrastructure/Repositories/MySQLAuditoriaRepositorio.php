<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Dominio\Entidades\Auditoria;
use App\Dominio\Repositorios\AuditoriaRepositorio;
use DateTimeImmutable;

final class MySQLAuditoriaRepositorio
    extends MySQLRepositorioBase
    implements AuditoriaRepositorio
{
    public function buscarPorId(int $idAuditoria): ?Auditoria
    {
        $sql = "
            SELECT
                id_auditoria,
                id_usuario,
                accion,
                tabla_afectada,
                fecha_hora,
                datos_anteriores,
                datos_nuevos,
                ip
            FROM auditoria
            WHERE id_auditoria = :id_auditoria
            LIMIT 1
        ";

        $r = $this->one(
            $sql,
            [
                ':id_auditoria' => $idAuditoria
            ]
        );

        return $r ? $this->map($r) : null;
    }

    public function listar(): array
    {
        $sql = "
            SELECT
                id_auditoria,
                id_usuario,
                accion,
                tabla_afectada,
                fecha_hora,
                datos_anteriores,
                datos_nuevos,
                ip
            FROM auditoria
            ORDER BY fecha_hora DESC
        ";

        $resultados = $this->all($sql);

        return array_map(
            fn(array $r): Auditoria => $this->map($r),
            $resultados
        );
    }

    public function listarPorUsuario(int $idUsuario): array
    {
        $sql = "
            SELECT
                id_auditoria,
                id_usuario,
                accion,
                tabla_afectada,
                fecha_hora,
                datos_anteriores,
                datos_nuevos,
                ip
            FROM auditoria
            WHERE id_usuario = :id_usuario
            ORDER BY fecha_hora DESC
        ";

        $resultados = $this->all(
            $sql,
            [
                ':id_usuario' => $idUsuario
            ]
        );

        return array_map(
            fn(array $r): Auditoria => $this->map($r),
            $resultados
        );
    }

    public function guardar(Auditoria $auditoria): void
    {
        $sql = "
            INSERT INTO auditoria (
                id_usuario,
                accion,
                tabla_afectada,
                fecha_hora,
                datos_anteriores,
                datos_nuevos,
                ip
            )
            VALUES (
                :id_usuario,
                :accion,
                :tabla_afectada,
                :fecha_hora,
                :datos_anteriores,
                :datos_nuevos,
                :ip
            )
        ";

        $this->exec(
            $sql,
            [
                ':id_usuario' =>
                    $auditoria->getIdUsuario(),

                ':accion' =>
                    $auditoria->getAccion(),

                ':tabla_afectada' =>
                    $auditoria->getTablaAfectada(),

                ':fecha_hora' =>
                    $auditoria->getFechaHora()
                        ->format('Y-m-d H:i:s'),

                ':datos_anteriores' =>
                    $auditoria->getDatosAnteriores(),

                ':datos_nuevos' =>
                    $auditoria->getDatosNuevos(),

                ':ip' =>
                    $auditoria->getIp()
            ]
        );
    }

    private function map(array $r): Auditoria
    {
        return new Auditoria(
            (int) $r['id_auditoria'],
            (int) $r['id_usuario'],
            (string) $r['accion'],
            (string) $r['tabla_afectada'],
            new DateTimeImmutable($r['fecha_hora']),
            $r['datos_anteriores'],
            $r['datos_nuevos'],
            $r['ip']
        );
    }
}
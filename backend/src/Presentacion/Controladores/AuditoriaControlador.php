<?php

declare(strict_types=1);

namespace App\Presentacion\Controladores;

use App\Dominio\Entidades\Auditoria;
use App\Dominio\Repositorios\AuditoriaRepositorio;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use Throwable;

final class AuditoriaControlador
{
    public function __construct(
        private readonly AuditoriaRepositorio $auditoriaRepositorio
    ) {}

    public function registrar(array $datos): array
    {
        try {
            $idUsuario       = (int)($datos['id_usuario'] ?? 0);
            $accion          = trim((string)($datos['accion'] ?? ''));
            $tablaAfectada   = trim((string)($datos['tabla_afectada'] ?? $datos['tabla'] ?? ''));
            $datosAnteriores = isset($datos['datos_anteriores']) ? (is_string($datos['datos_anteriores']) ? $datos['datos_anteriores'] : json_encode($datos['datos_anteriores'])) : null;
            $datosNuevos     = isset($datos['datos_nuevos']) ? (is_string($datos['datos_nuevos']) ? $datos['datos_nuevos'] : json_encode($datos['datos_nuevos'])) : null;
            $ip              = trim((string)($datos['ip'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'));

            $auditoria = new Auditoria(
                0,
                $idUsuario,
                $accion,
                $tablaAfectada,
                new DateTimeImmutable(),
                $datosAnteriores,
                $datosNuevos,
                $ip
            );

            $this->auditoriaRepositorio->guardar($auditoria);

            return [
                'success' => true,
                'message' => 'Evento de auditoría registrado correctamente.',
                'data'    => $this->mapAuditoria($auditoria)
            ];
        } catch (InvalidArgumentException | DomainException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al registrar auditoría: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function buscarPorId(int $idAuditoria): array
    {
        try {
            $auditoria = $this->auditoriaRepositorio->buscarPorId($idAuditoria);
            if ($auditoria === null) {
                return [
                    'success' => false,
                    'message' => "Registro de auditoría con ID {$idAuditoria} no encontrado.",
                    'data'    => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Registro de auditoría encontrado.',
                'data'    => $this->mapAuditoria($auditoria)
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al buscar auditoría: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function listar(): array
    {
        try {
            $auditorias = $this->auditoriaRepositorio->listar();
            $data = array_map(fn(Auditoria $a) => $this->mapAuditoria($a), $auditorias);

            return [
                'success' => true,
                'message' => 'Registros de auditoría obtenidos.',
                'data'    => $data
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al listar auditoría: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function listarPorUsuario(int $idUsuario): array
    {
        try {
            $auditorias = $this->auditoriaRepositorio->listarPorUsuario($idUsuario);
            $data = array_map(fn(Auditoria $a) => $this->mapAuditoria($a), $auditorias);

            return [
                'success' => true,
                'message' => 'Registros de auditoría del usuario obtenidos.',
                'data'    => $data
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al listar auditoría por usuario: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    private function mapAuditoria(Auditoria $a): array
    {
        return [
            'id_auditoria'     => $a->getIdAuditoria(),
            'id_usuario'       => $a->getIdUsuario(),
            'accion'           => $a->getAccion(),
            'tabla_afectada'   => $a->getTablaAfectada(),
            'fecha_hora'       => $a->getFechaHora()->format('Y-m-d H:i:s'),
            'datos_anteriores' => $a->getDatosAnteriores(),
            'datos_nuevos'     => $a->getDatosNuevos(),
            'ip'               => $a->getIp()
        ];
    }
}

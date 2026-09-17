<?php

declare(strict_types=1);

namespace App\Presentacion\Controladores;

use App\Dominio\Entidades\Sesion;
use App\Dominio\Repositorios\SesionRepositorio;
use App\Dominio\Repositorios\UsuarioRepositorio;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use Throwable;

final class SesionControlador
{
    public function __construct(
        private readonly SesionRepositorio $sesionRepositorio,
        private readonly ?UsuarioRepositorio $usuarioRepositorio = null
    ) {}

    public function iniciarSesion(array $datos): array
    {
        try {
            $idUsuario = (int)($datos['id_usuario'] ?? $datos['usuario_id'] ?? 0);
            $token     = trim((string)($datos['token'] ?? bin2hex(random_bytes(32))));
            $ip        = trim((string)($datos['ip'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'));

            if ($idUsuario <= 0) {
                throw new InvalidArgumentException('El ID del usuario es obligatorio para iniciar sesión.');
            }

            $sesion = new Sesion(
                1,
                $idUsuario,
                $token,
                new DateTimeImmutable(),
                null,
                $ip,
                true
            );

            $this->sesionRepositorio->guardar($sesion);

            return [
                'success' => true,
                'message' => 'Sesión iniciada correctamente.',
                'data'    => $this->mapSesion($sesion)
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
                'message' => 'Error al iniciar sesión: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function buscarPorToken(string $token): array
    {
        try {
            $sesion = $this->sesionRepositorio->buscarPorToken($token);
            if ($sesion === null) {
                return [
                    'success' => false,
                    'message' => 'Sesión no encontrada o token inválido.',
                    'data'    => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Sesión activa encontrada.',
                'data'    => $this->mapSesion($sesion)
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al verificar sesión: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function cerrarSesion(string $token): array
    {
        try {
            $sesion = $this->sesionRepositorio->buscarPorToken($token);
            if ($sesion === null) {
                return [
                    'success' => false,
                    'message' => 'Sesión no encontrada.',
                    'data'    => null
                ];
            }

            $sesion->cerrarSesion();
            $this->sesionRepositorio->actualizar($sesion);

            return [
                'success' => true,
                'message' => 'Sesión cerrada correctamente.',
                'data'    => $this->mapSesion($sesion)
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
                'message' => 'Error al cerrar sesión: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function listarPorUsuario(int $idUsuario): array
    {
        try {
            $sesiones = $this->sesionRepositorio->listarPorUsuario($idUsuario);
            $data = array_map(fn(Sesion $s) => $this->mapSesion($s), $sesiones);

            return [
                'success' => true,
                'message' => 'Sesiones del usuario listadas correctamente.',
                'data'    => $data
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al listar sesiones: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    private function mapSesion(Sesion $s): array
    {
        return [
            'id_sesion'    => $s->getIdSesion(),
            'id_usuario'   => $s->getIdUsuario(),
            'token'        => $s->getToken(),
            'fecha_inicio' => $s->getFechaInicio()->format('Y-m-d H:i:s'),
            'fecha_fin'    => $s->getFechaFin()?->format('Y-m-d H:i:s'),
            'ip'           => $s->getIp(),
            'activa'       => $s->getActiva()
        ];
    }
}

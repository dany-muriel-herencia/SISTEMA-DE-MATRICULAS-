<?php

declare(strict_types=1);

namespace App\Presentacion\Controladores;

use App\Dominio\Entidades\Administrador;
use App\Dominio\Repositorios\UsuarioRepositorio;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use Throwable;

final class AdministradorControlador
{
    public function __construct(
        private readonly UsuarioRepositorio $usuarioRepositorio
    ) {}

    public function registrar(array $datos): array
    {
        try {
            $nombre      = trim((string)($datos['nombre'] ?? ''));
            $email       = trim((string)($datos['email'] ?? ''));
            $contrasenha = (string)($datos['contrasenha'] ?? $datos['password'] ?? '');
            $rol         = 'ADMINISTRADOR';
            $estado      = isset($datos['estado']) ? (bool)$datos['estado'] : true;
            $nivel       = trim((string)($datos['nivel'] ?? 'SUPERADMIN'));

            $admin = new Administrador(
                0,
                $nombre,
                $email,
                $contrasenha,
                $rol,
                $estado,
                new DateTimeImmutable(),
                $nivel
            );

            $this->usuarioRepositorio->guardar($admin);

            return [
                'success' => true,
                'message' => 'Administrador registrado correctamente.',
                'data'    => $this->mapAdmin($admin)
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
                'message' => 'Error al registrar administrador: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function buscarPorId(int $idUsuario): array
    {
        try {
            $usuario = $this->usuarioRepositorio->buscarPorId($idUsuario);
            if ($usuario === null || strtoupper($usuario->getRol()) !== 'ADMINISTRADOR') {
                return [
                    'success' => false,
                    'message' => "Administrador con ID {$idUsuario} no encontrado.",
                    'data'    => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Administrador encontrado.',
                'data'    => [
                    'id_usuario'     => $usuario->getIdUsuario(),
                    'nombre'         => $usuario->getNombre(),
                    'email'          => $usuario->getEmail(),
                    'rol'            => $usuario->getRol(),
                    'estado'         => $usuario->isEstado(),
                    'fecha_creacion' => $usuario->getFechaCreacion()->format('Y-m-d H:i:s')
                ]
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al buscar administrador: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function actualizar(array $datos): array
    {
        try {
            $idUsuario = (int)($datos['id_usuario'] ?? $datos['id'] ?? 0);
            $usuarioExistente = $this->usuarioRepositorio->buscarPorId($idUsuario);
            if ($usuarioExistente === null) {
                return [
                    'success' => false,
                    'message' => "Administrador con ID {$idUsuario} no encontrado.",
                    'data'    => null
                ];
            }

            $nombre      = trim((string)($datos['nombre'] ?? $usuarioExistente->getNombre()));
            $email       = trim((string)($datos['email'] ?? $usuarioExistente->getEmail()));
            $contrasenha = (string)($datos['contrasenha'] ?? $datos['password'] ?? $usuarioExistente->getContrasenha());
            $nivel       = trim((string)($datos['nivel'] ?? 'SUPERADMIN'));

            $actualizado = new Administrador(
                $idUsuario,
                $nombre,
                $email,
                $contrasenha,
                'ADMINISTRADOR',
                $usuarioExistente->isEstado(),
                $usuarioExistente->getFechaCreacion(),
                $nivel
            );

            $this->usuarioRepositorio->actualizar($actualizado);

            return [
                'success' => true,
                'message' => 'Administrador actualizado correctamente.',
                'data'    => $this->mapAdmin($actualizado)
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
                'message' => 'Error al actualizar administrador: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    private function mapAdmin(Administrador $a): array
    {
        return [
            'id_usuario'     => $a->getIdUsuario(),
            'nombre'         => $a->getNombre(),
            'email'          => $a->getEmail(),
            'rol'            => $a->getRol(),
            'nivel'          => $a->getNivel(),
            'estado'         => $a->isEstado(),
            'fecha_creacion' => $a->getFechaCreacion()->format('Y-m-d H:i:s')
        ];
    }
}

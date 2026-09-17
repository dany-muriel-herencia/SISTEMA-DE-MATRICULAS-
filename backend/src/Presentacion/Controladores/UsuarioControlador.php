<?php

declare(strict_types=1);

namespace App\Presentacion\Controladores;

use App\Dominio\Entidades\Usuario;
use App\Dominio\Repositorios\UsuarioRepositorio;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use Throwable;

final class UsuarioControlador
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
            $rol         = strtoupper(trim((string)($datos['rol'] ?? 'ESTUDIANTE')));
            $estado      = isset($datos['estado']) ? (bool)$datos['estado'] : true;

            $usuario = new Usuario(
                0,
                $nombre,
                $email,
                $contrasenha,
                $rol,
                $estado,
                new DateTimeImmutable()
            );

            $this->usuarioRepositorio->guardar($usuario);

            return [
                'success' => true,
                'message' => 'Usuario registrado exitosamente.',
                'data'    => $this->mapUsuario($usuario)
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
                'message' => 'Error al registrar usuario: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function buscarPorId(int $idUsuario): array
    {
        try {
            $usuario = $this->usuarioRepositorio->buscarPorId($idUsuario);
            if ($usuario === null) {
                return [
                    'success' => false,
                    'message' => "Usuario con ID {$idUsuario} no encontrado.",
                    'data'    => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Usuario encontrado.',
                'data'    => $this->mapUsuario($usuario)
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al buscar usuario: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function buscarPorEmail(string $email): array
    {
        try {
            $usuario = $this->usuarioRepositorio->buscarPorEmail($email);
            if ($usuario === null) {
                return [
                    'success' => false,
                    'message' => "Usuario con email {$email} no encontrado.",
                    'data'    => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Usuario encontrado.',
                'data'    => $this->mapUsuario($usuario)
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al buscar usuario por email: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function listar(int $limit = 50, int $offset = 0): array
    {
        try {
            $usuarios = $this->usuarioRepositorio->listar($limit, $offset);
            $data = array_map(fn(Usuario $u) => $this->mapUsuario($u), $usuarios);

            return [
                'success' => true,
                'message' => 'Usuarios listados correctamente.',
                'data'    => $data
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al listar usuarios: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function actualizar(array $datos): array
    {
        try {
            $idUsuario   = (int)($datos['id_usuario'] ?? $datos['id'] ?? 0);
            $usuarioExistente = $this->usuarioRepositorio->buscarPorId($idUsuario);
            if ($usuarioExistente === null) {
                return [
                    'success' => false,
                    'message' => "Usuario con ID {$idUsuario} no encontrado.",
                    'data'    => null
                ];
            }

            $nombre      = trim((string)($datos['nombre'] ?? $usuarioExistente->getNombre()));
            $email       = trim((string)($datos['email'] ?? $usuarioExistente->getEmail()));
            $contrasenha = (string)($datos['contrasenha'] ?? $datos['password'] ?? $usuarioExistente->getContrasenha());
            $rol         = strtoupper(trim((string)($datos['rol'] ?? $usuarioExistente->getRol())));
            $estado      = isset($datos['estado']) ? (bool)$datos['estado'] : $usuarioExistente->isEstado();

            $usuarioActualizado = new Usuario(
                $idUsuario,
                $nombre,
                $email,
                $contrasenha,
                $rol,
                $estado,
                $usuarioExistente->getFechaCreacion()
            );

            $this->usuarioRepositorio->actualizar($usuarioActualizado);

            return [
                'success' => true,
                'message' => 'Usuario actualizado correctamente.',
                'data'    => $this->mapUsuario($usuarioActualizado)
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
                'message' => 'Error al actualizar usuario: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function eliminar(int $idUsuario): array
    {
        try {
            $this->usuarioRepositorio->eliminar($idUsuario);

            return [
                'success' => true,
                'message' => "Usuario con ID {$idUsuario} eliminado correctamente.",
                'data'    => ['id_usuario' => $idUsuario]
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al eliminar usuario: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    private function mapUsuario(Usuario $u): array
    {
        return [
            'id_usuario'     => $u->getIdUsuario(),
            'nombre'         => $u->getNombre(),
            'email'          => $u->getEmail(),
            'rol'            => $u->getRol(),
            'estado'         => $u->isEstado(),
            'fecha_creacion' => $u->getFechaCreacion()->format('Y-m-d H:i:s')
        ];
    }
}

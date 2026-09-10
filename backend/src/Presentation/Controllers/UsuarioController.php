<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\DTO\CrearUsuarioDTO;
use App\Application\UseCases\Usuario\CrearUsuario;
use App\Application\UseCases\Usuario\ConsultarUsuario;
use App\Presentation\Responses\ApiResponse;
use DomainException;
use InvalidArgumentException;
use Throwable;

class UsuarioController
{
    private CrearUsuario $crearUsuario;
    private ConsultarUsuario $consultarUsuario;

    public function __construct(CrearUsuario $crearUsuario, ConsultarUsuario $consultarUsuario)
    {
        $this->crearUsuario = $crearUsuario;
        $this->consultarUsuario = $consultarUsuario;
    }

    public function listar(): void
    {
        try {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            $usuarios = $this->consultarUsuario->listar($limit, $offset);
            $data = array_map(fn($u) => $u->toArray(), $usuarios);
            ApiResponse::success($data, "Lista de usuarios obtenida.");
        } catch (Throwable $e) {
            ApiResponse::error("Error al listar usuarios: " . $e->getMessage(), 500);
        }
    }

    public function consultar(int $id): void
    {
        try {
            $usuario = $this->consultarUsuario->ejecutarPorId($id);
            ApiResponse::success($usuario->toArray(), "Usuario obtenido correctamente.");
        } catch (DomainException $e) {
            ApiResponse::notFound($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error("Error al consultar usuario: " . $e->getMessage(), 500);
        }
    }

    public function registrar(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto = CrearUsuarioDTO::fromArray($input);
            $usuario = $this->crearUsuario->ejecutar($dto);

            ApiResponse::created($usuario->toArray(), "Usuario creado exitosamente.");
        } catch (InvalidArgumentException | DomainException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error("Error al registrar usuario: " . $e->getMessage(), 500);
        }
    }
}

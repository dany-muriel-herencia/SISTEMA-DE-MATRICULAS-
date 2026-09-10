<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\DTO\LoginDTO;
use App\Application\UseCases\Usuario\AutenticarUsuario;
use App\Presentation\Responses\ApiResponse;
use DomainException;
use InvalidArgumentException;
use Throwable;

class AuthController
{
    private AutenticarUsuario $autenticarUsuario;

    public function __construct(AutenticarUsuario $autenticarUsuario)
    {
        $this->autenticarUsuario = $autenticarUsuario;
    }

    public function login(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto = LoginDTO::fromArray($input);

            $usuario = $this->autenticarUsuario->ejecutar($dto);

            // Generar token JWT / Bearer
            $token = base64_encode(json_encode([
                'user_id' => $usuario->getId(),
                'email' => $usuario->getEmail()->getValue(),
                'rol_id' => $usuario->getRolId(),
                'exp' => time() + 86400,
            ]));

            ApiResponse::success([
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 86400,
                'user' => $usuario->toArray(),
            ], "Inicio de sesión exitoso.");
        } catch (InvalidArgumentException | DomainException $e) {
            ApiResponse::unauthorized($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error("Error de autenticación: " . $e->getMessage(), 500);
        }
    }
}

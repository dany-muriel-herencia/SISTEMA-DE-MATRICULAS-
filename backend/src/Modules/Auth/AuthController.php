<?php

declare(strict_types=1);

namespace App\Modules\Auth;

use App\Core\ApiResponse;
use App\Core\AuthMiddleware;
use App\Core\Router;

final class AuthController
{
    private AuthRepository $repo;

    public function __construct()
    {
        $this->repo = new AuthRepository();
    }

    // CU-01: Login con bloqueo al 3er intento fallido
    public function login(): void
    {
        $body = Router::getJsonBody();
        $identificador = trim((string)($body['correo'] ?? $body['email'] ?? $body['usuario'] ?? ''));
        $password = (string)($body['password'] ?? '');

        if ($identificador === '' || $password === '') {
            ApiResponse::error('Debe ingresar su correo/código institucional y contraseña.', 400, 'DATOS_INCOMPLETOS');
            return;
        }

        $user = $this->repo->buscarPorEmailOCodigo($identificador);
        if (!$user) {
            ApiResponse::error('Credenciales inválidas.', 401, 'CREDENCIALES_INVALIDAS');
            return;
        }

        if ($user['estado'] === 'BLOQUEADO') {
            ApiResponse::error('Cuenta bloqueada por superar 3 intentos fallidos. Contacte a OTI.', 403, 'CUENTA_BLOQUEADA');
            return;
        }

        $hash = (string)$user['password_hash'];
        $passwordValido = password_verify($password, $hash) || hash_equals($hash, $password);

        if (!$passwordValido) {
            $nuevoEstado = $this->repo->registrarIntentoFallido((int)$user['id_usuario'], (int)$user['intentos_fallidos']);
            $this->repo->registrarAuditoria((int)$user['id_usuario'], 'AUTH', 'LOGIN_FALLIDO', 'usuarios', (int)$user['id_usuario'], "Estado resultante: {$nuevoEstado}");
            ApiResponse::error('Contraseña incorrecta.', 401, $nuevoEstado === 'BLOQUEADO' ? 'CUENTA_BLOQUEADA' : 'CREDENCIALES_INVALIDAS');
            return;
        }

        $this->repo->registrarAccesoExitoso((int)$user['id_usuario']);
        $this->repo->registrarAuditoria((int)$user['id_usuario'], 'AUTH', 'LOGIN_EXITOSO', 'usuarios', (int)$user['id_usuario'], 'Inicio de sesión');

        $token = AuthMiddleware::generateToken([
            'idUsuario' => (int)$user['id_usuario'],
            'idEstudiante' => $user['id_estudiante'] ? (int)$user['id_estudiante'] : null,
            'idRol' => (int)$user['id_rol'],
            'rol' => $user['nombre_rol'],
            'correo' => $user['email_institucional'],
        ]);

        unset($user['password_hash']);
        ApiResponse::success([
            'token' => $token,
            'usuario' => $user,
        ], 'Autenticación exitosa.');
    }

    // CU-02: Logout
    public function logout(): void
    {
        ApiResponse::success(null, 'Sesión cerrada correctamente.');
    }

    // CU-06: Listar cuentas de usuario
    public function listarUsuarios(): void
    {
        ApiResponse::success($this->repo->listarUsuarios());
    }

    // CU-05: Asignar rol a usuario
    public function asignarRol(): void
    {
        $body = Router::getJsonBody();
        $idUsuario = (int)($body['idUsuario'] ?? 0);
        $idRol = (int)($body['idRol'] ?? 0);
        $idAdmin = isset($body['idAdmin']) ? (int)$body['idAdmin'] : null;

        if ($idUsuario <= 0 || $idRol <= 0) {
            ApiResponse::error('idUsuario e idRol son obligatorios.', 400);
            return;
        }

        $this->repo->asignarRol($idUsuario, $idRol, $idAdmin);
        ApiResponse::success(['idUsuario' => $idUsuario, 'idRol' => $idRol], 'Rol actualizado en bitácora.');
    }

    // CU-07: Consultar bitácora de auditoría
    public function listarAuditoria(): void
    {
        ApiResponse::success($this->repo->listarAuditoria());
    }
}

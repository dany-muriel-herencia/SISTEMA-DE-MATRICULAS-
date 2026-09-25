<?php
declare(strict_types=1);
namespace App\Presentation\Controllers;
use App\Application\CasoDeUso\Auth\IniciarSesion;
use App\Application\CasoDeUso\Auth\CerrarSesion;
use App\Application\DTO\LoginDTO;
use App\Presentation\Responses\ApiResponse;
final class AuthController {
    public function __construct(private IniciarSesion $iniciar,private CerrarSesion $cerrar) {}
    public function login(): void {
        try {
            $dto=LoginDTO::fromArray(json_decode(file_get_contents('php://input'),true)??[]);
            $s=$this->iniciar->ejecutar($dto->getEmail()->getValue(),$dto->getPassword(),$_SERVER['REMOTE_ADDR']??'127.0.0.1');
            ApiResponse::success(['token'=>$s->getToken(),'token_type'=>'Bearer','expires_in'=>max(1,(int)($_ENV['SESSION_EXPIRATION']??86400))]);
        } catch (\PDOException $e) { error_log((string)$e); ApiResponse::error('Error interno de autenticación.',500); }
        catch (\InvalidArgumentException|\RuntimeException $e) { ApiResponse::unauthorized('Credenciales inválidas.'); }
    }
    public function logout(): void {
        $token=substr($_SERVER['HTTP_AUTHORIZATION']??'',7);
        $this->cerrar->ejecutar($token);
        ApiResponse::success(null,'Sesión cerrada.');
    }
    public function me(): void {
        ApiResponse::success(\App\Presentation\Middleware\AuthMiddleware::$usuario->toArray());
    }
}

<?php
declare(strict_types=1);
namespace App\Presentation\Middleware;
use App\Presentation\Responses\ApiResponse;
final class RoleMiddleware implements MiddlewareInterface {
    public function __construct(private array $allowedRoles=[]) {}
    public function handle(array $params=[]): bool {
        $u=AuthMiddleware::$usuario;
        if(!$u || !in_array($u->getRol(),$this->allowedRoles,true)) {
            ApiResponse::forbidden('No cuenta con permisos para esta acción.');
            return false;
        }
        return true;
    }
}

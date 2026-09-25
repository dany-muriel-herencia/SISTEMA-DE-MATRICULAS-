<?php
declare(strict_types=1);
namespace App\Presentation\Middleware;
use App\Dominio\Entidades\Usuario;
use App\Dominio\Repositorios\SesionRepositorio;
use App\Dominio\Repositorios\UsuarioRepositorio;
use App\Presentation\Responses\ApiResponse;
final class AuthMiddleware implements MiddlewareInterface {
    public static ?Usuario $usuario = null;
    public function __construct(private SesionRepositorio $sesiones,private UsuarioRepositorio $usuarios) {}
    public function autenticar(string $token): ?Usuario {
        self::$usuario=null;
        $s=$this->sesiones->buscarPorToken($token);
        $ttl=max(1,(int)($_ENV['SESSION_EXPIRATION']??86400));
        if(!$s || !$s->getActiva() || $s->getFechaInicio()->getTimestamp()+$ttl<=time()) return null;
        $u=$this->usuarios->buscarPorId($s->getIdUsuario());
        return self::$usuario=($u && $u->estaActivo()) ? $u : null;
    }
    public function handle(array $params=[]): bool {
        $header=$_SERVER['HTTP_AUTHORIZATION']??'';
        if(!preg_match('/^Bearer ([a-f0-9]{64})$/D',$header,$m) || !$this->autenticar($m[1])) {
            ApiResponse::unauthorized('Sesión inválida o expirada.');
            return false;
        }
        return true;
    }
}

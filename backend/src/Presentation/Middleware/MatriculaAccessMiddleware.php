<?php
declare(strict_types=1);
namespace App\Presentation\Middleware;
use App\Dominio\Repositorios\MatriculaRepositorio;
use App\Presentation\Responses\ApiResponse;
final class MatriculaAccessMiddleware implements MiddlewareInterface {
    public function __construct(private MatriculaRepositorio $repo,private string $mode) {}
    public function handle(array $params=[]): bool {
        $u=AuthMiddleware::$usuario;
        if($u && in_array($u->getRol(),['ADMIN','COORDINADOR'],true)) return true;
        $owner=null;
        if($this->mode==='crear') {
            $d=json_decode(file_get_contents('php://input'),true);
            $owner=is_array($d)?(int)($d['estudiante_id']??$d['idEstudiante']??0):0;
        } elseif($this->mode==='estudiante') { $owner=(int)($params['id']??0); }
        else {
            $m=$this->mode==='codigo'?$this->repo->buscarPorCodigo((string)($params['codigo']??'')):$this->repo->buscarPorId((int)($params['id']??0));
            $owner=$m?->getIdEstudiante();
        }
        if(!$u || $u->getRol()!=='ESTUDIANTE' || $owner!==$u->getIdUsuario()) {
            ApiResponse::forbidden('No puede acceder a matrículas de otro estudiante.'); return false;
        }
        return true;
    }
}

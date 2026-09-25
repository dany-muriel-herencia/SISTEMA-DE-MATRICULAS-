<?php
declare(strict_types=1);
namespace App\Application\CasoDeUso\Curso;
use App\Dominio\Entidades\Curso;
use App\Dominio\Repositorios\CursoRepositorio;
final class ConsultarCursos {
    public function __construct(private CursoRepositorio $repo) {}
    public function ejecutarPorId(int $id): Curso { return $this->repo->buscarPorId($id) ?? throw new \DomainException('Curso no encontrado.'); }
    public function listar(int $limit=50,int $offset=0): array { return $this->repo->listar($limit,$offset); }
    public function listarOfertaPorPeriodoYCarrera(int $periodo,int $carrera): array { return $this->repo->listarOfertaPorPeriodoYCarrera($periodo,$carrera); }
}

<?php
declare(strict_types=1);
namespace App\Application\CasoDeUso\Curso;
use App\Application\DTO\CrearCursoDTO;
use App\Dominio\Entidades\Curso;
use App\Dominio\Repositorios\CursoRepositorio;
final class CrearCurso {
    public function __construct(private CursoRepositorio $repo) {}
    public function ejecutar(CrearCursoDTO $d): Curso {
        if($this->repo->buscarPorCodigo($d->getCodigo())) throw new \DomainException('Código de curso ya registrado.');
        $c=new Curso(0,$d->getNombre(),$d->getCodigo(),$d->getCreditos(),$d->getHorasTeoricas(),$d->getHorasPracticas(),$d->getCiclo(),true);
        $this->repo->guardar($c); return $c;
    }
}

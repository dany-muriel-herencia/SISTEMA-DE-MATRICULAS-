<?php
declare(strict_types=1);
namespace App\Application\CasoDeUso\Estudiante;
use App\Application\DTO\CrearEstudianteDTO;
use App\Dominio\Entidades\Estudiante;
use App\Dominio\Repositorios\EstudianteRepositorio;
use App\Dominio\Repositorios\UsuarioRepositorio;
final class CrearEstudiante {
    public function __construct(private EstudianteRepositorio $estudianteRepo,private UsuarioRepositorio $usuarioRepo) {}
    public function ejecutar(CrearEstudianteDTO $d): Estudiante {
        $u=$this->usuarioRepo->buscarPorId($d->usuarioId);
        if(!$u || $u->getRol()!=='ESTUDIANTE') throw new \DomainException('Se requiere un usuario con rol ESTUDIANTE.');
        if($this->estudianteRepo->buscarPorId($d->usuarioId) || $this->estudianteRepo->buscarPorCodigo($d->codigo) || $this->estudianteRepo->buscarPorDni($d->dni)) throw new \DomainException('Usuario, código o DNI ya registrado.');
        $e=new Estudiante($u->getIdUsuario(),$u->getNombre(),$u->getEmail(),$u->getContrasenha(),$u->getRol(),$u->getEstado(),$u->getFechaCreacion(),$d->codigo,$d->dni,$d->nacimiento,$d->ingreso,0);
        $this->estudianteRepo->guardar($e);
        return $e;
    }
}

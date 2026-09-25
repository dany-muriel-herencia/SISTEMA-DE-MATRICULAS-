<?php
declare(strict_types=1);
namespace App\Application\CasoDeUso\Usuario;
use App\Application\DTO\CrearUsuarioDTO;
use App\Dominio\Entidades\Usuario;
use App\Dominio\Repositorios\UsuarioRepositorio;
final class CrearUsuario {
    public function __construct(private UsuarioRepositorio $usuarioRepo) {}
    public function ejecutar(CrearUsuarioDTO $dto): Usuario {
        if($this->usuarioRepo->buscarPorEmail($dto->getEmail())) throw new \DomainException('Correo ya registrado.');
        $u=new Usuario(0,$dto->getNombre(),$dto->getEmail(),password_hash($dto->getPassword(),PASSWORD_DEFAULT),$dto->getRol(),true,new \DateTimeImmutable());
        $this->usuarioRepo->guardar($u);
        return $u;
    }
}

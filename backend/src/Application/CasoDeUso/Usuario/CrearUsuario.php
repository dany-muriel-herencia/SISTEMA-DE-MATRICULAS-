<?php

declare(strict_types=1);

namespace App\Application\UseCases\Usuario;

use App\Application\DTO\CrearUsuarioDTO;
use App\Domain\Entities\Usuario;
use App\Domain\Repositories\UsuarioRepositoryInterface;
use DomainException;
use RuntimeException;

class CrearUsuario
{
    private UsuarioRepositoryInterface $usuarioRepo;

    public function __construct(UsuarioRepositoryInterface $usuarioRepo)
    {
        $this->usuarioRepo = $usuarioRepo;
    }

    public function ejecutar(CrearUsuarioDTO $dto): Usuario
    {
        if ($this->usuarioRepo->buscarPorEmail($dto->getEmail())) {
            throw new DomainException("El correo electrónico '{$dto->getEmail()}' ya se encuentra registrado.");
        }
        if ($this->usuarioRepo->buscarPorDni($dto->getDni())) {
            throw new DomainException("El DNI '{$dto->getDni()}' ya se encuentra registrado.");
        }

        $passwordHash = password_hash($dto->getPassword(), PASSWORD_BCRYPT);

        $usuario = new Usuario(
            $dto->getRolId(),
            $dto->getDni(),
            $dto->getEmail(),
            $passwordHash,
            $dto->getNombre(),
            $dto->getApellido(),
            $dto->getTelefono(),
            true
        );

        $id = $this->usuarioRepo->guardar($usuario);
        if ($id <= 0) {
            throw new RuntimeException("No se pudo crear el usuario en la base de datos.");
        }
        $usuario->setId($id);

        return $usuario;
    }
}

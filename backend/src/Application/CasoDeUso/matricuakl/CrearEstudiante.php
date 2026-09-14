<?php

declare(strict_types=1);

namespace App\Application\UseCases\Estudiante;

use App\Application\DTO\CrearEstudianteDTO;
use App\Domain\Entities\Estudiante;
use App\Domain\Repositories\EstudianteRepositoryInterface;
use App\Domain\Repositories\UsuarioRepositoryInterface;
use DomainException;
use RuntimeException;

class CrearEstudiante
{
    private EstudianteRepositoryInterface $estudianteRepo;
    private UsuarioRepositoryInterface $usuarioRepo;

    public function __construct(
        EstudianteRepositoryInterface $estudianteRepo,
        UsuarioRepositoryInterface $usuarioRepo
    ) {
        $this->estudianteRepo = $estudianteRepo;
        $this->usuarioRepo = $usuarioRepo;
    }

    public function ejecutar(CrearEstudianteDTO $dto): Estudiante
    {
        $usuario = $this->usuarioRepo->buscarPorId($dto->getUsuarioId());
        if (!$usuario) {
            throw new DomainException("El usuario con ID {$dto->getUsuarioId()} no existe.");
        }

        if ($this->estudianteRepo->buscarPorCodigo($dto->getCodigoEstudiante())) {
            throw new DomainException("El código de estudiante '{$dto->getCodigoEstudiante()}' ya está registrado.");
        }

        if ($this->estudianteRepo->buscarPorUsuarioId($dto->getUsuarioId())) {
            throw new DomainException("El usuario ya tiene un perfil de estudiante asociado.");
        }

        $estudiante = new Estudiante(
            $dto->getUsuarioId(),
            $dto->getCarreraId(),
            $dto->getPlanEstudioId(),
            $dto->getCodigoEstudiante(),
            $dto->getAnioIngreso(),
            $dto->getEstadoAcademico(),
            $usuario
        );

        $id = $this->estudianteRepo->guardar($estudiante);
        if ($id <= 0) {
            throw new RuntimeException("No se pudo registrar el estudiante.");
        }
        $estudiante->setId($id);

        return $estudiante;
    }
}

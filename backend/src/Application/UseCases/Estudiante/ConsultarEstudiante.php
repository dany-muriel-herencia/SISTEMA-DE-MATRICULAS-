<?php

declare(strict_types=1);

namespace App\Application\UseCases\Estudiante;

use App\Domain\Entities\Estudiante;
use App\Domain\Repositories\EstudianteRepositoryInterface;
use DomainException;

class ConsultarEstudiante
{
    private EstudianteRepositoryInterface $estudianteRepo;

    public function __construct(EstudianteRepositoryInterface $estudianteRepo)
    {
        $this->estudianteRepo = $estudianteRepo;
    }

    public function ejecutarPorId(int $id): Estudiante
    {
        $estudiante = $this->estudianteRepo->buscarPorId($id);
        if (!$estudiante) {
            throw new DomainException("Estudiante con ID {$id} no encontrado.");
        }
        return $estudiante;
    }

    public function ejecutarPorCodigo(string $codigo): Estudiante
    {
        $estudiante = $this->estudianteRepo->buscarPorCodigo($codigo);
        if (!$estudiante) {
            throw new DomainException("Estudiante con código {$codigo} no encontrado.");
        }
        return $estudiante;
    }
}

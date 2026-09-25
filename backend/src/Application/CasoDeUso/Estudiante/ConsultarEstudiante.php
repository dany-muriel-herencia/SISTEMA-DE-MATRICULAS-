<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Estudiante;

use App\Dominio\Entidades\Estudiante;
use App\Dominio\Repositorios\EstudianteRepositorio;
use DomainException;

class ConsultarEstudiante
{
    private EstudianteRepositorio $estudianteRepo;

    public function __construct(EstudianteRepositorio $estudianteRepo)
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

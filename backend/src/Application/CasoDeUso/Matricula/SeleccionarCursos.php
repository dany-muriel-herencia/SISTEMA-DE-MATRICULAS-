<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Matricula;

use App\Dominio\Entidades\Matricula;
use App\Dominio\Repositorios\MatriculaRepositorio;
use DomainException;

class ConsultarMatricula
{
    private MatriculaRepositorio $matriculaRepo;

    public function __construct(MatriculaRepositorio $matriculaRepo)
    {
        $this->matriculaRepo = $matriculaRepo;
    }

    public function ejecutarPorId(int $id): Matricula
    {
        $matricula = $this->matriculaRepo->buscarPorId($id);
        if (!$matricula) {
            throw new DomainException("No se encontró la matrícula con ID {$id}.");
        }
        return $matricula;
    }

    public function ejecutarPorCodigo(string $codigo): Matricula
    {
        $matricula = $this->matriculaRepo->buscarPorCodigo($codigo);
        if (!$matricula) {
            throw new DomainException("No se encontró la matrícula con código {$codigo}.");
        }
        return $matricula;
    }

    public function listarPorEstudiante(int $estudianteId): array
    {
        return $this->matriculaRepo->listarPorEstudiante($estudianteId);
    }
}

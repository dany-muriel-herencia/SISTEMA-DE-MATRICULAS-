<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Matricula;

use App\Dominio\Entidades\Matricula;
use App\Dominio\Repositorios\MatriculaRepositorio;
use DomainException;
use RuntimeException;

class AnularMatricula
{
    private MatriculaRepositorio $matriculaRepo;

    public function __construct(MatriculaRepositorio $matriculaRepo)
    {
        $this->matriculaRepo = $matriculaRepo;
    }

    public function ejecutar(int $matriculaId): Matricula
    {
        $matricula = $this->matriculaRepo->buscarPorId($matriculaId);
        if (!$matricula) {
            throw new DomainException("No se encontró la matrícula con ID {$matriculaId}.");
        }
        if ($matricula->getEstado() === 'ANULADA') {
            throw new DomainException("La matrícula ya se encuentra anulada.");
        }

        // Anular lógica en la entidad de dominio
        $matricula->anular();

        // Actualizar estado en persistencia
        $this->matriculaRepo->anularConDetalles($matriculaId);

        return $matricula;
    }
}

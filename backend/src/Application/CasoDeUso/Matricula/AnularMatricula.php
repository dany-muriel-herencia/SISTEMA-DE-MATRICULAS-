<?php

declare(strict_types=1);

namespace App\Application\UseCases\Matricula;

use App\Domain\Entities\Matricula;
use App\Domain\Repositories\MatriculaRepositoryInterface;
use DomainException;
use RuntimeException;

class AnularMatricula
{
    private MatriculaRepositoryInterface $matriculaRepo;

    public function __construct(MatriculaRepositoryInterface $matriculaRepo)
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
        $exito = $this->matriculaRepo->anular($matriculaId);
        if (!$exito) {
            throw new RuntimeException("No se pudo actualizar el estado de anulación en la base de datos.");
        }

        // Devolver vacantes a las secciones
        foreach ($matricula->getDetalles() as $detalle) {
            $this->matriculaRepo->incrementarCupoSeccion($detalle->getSeccionId());
        }

        return $matricula;
    }
}

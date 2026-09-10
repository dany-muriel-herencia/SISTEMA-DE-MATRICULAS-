<?php

declare(strict_types=1);

namespace App\Infrastructure\Queue;

use App\Application\DTO\RegistrarMatriculaDTO;
use App\Application\UseCases\Matricula\RegistrarMatricula;
use Throwable;

class MatriculaWorker
{
    private QueueManager $queueManager;
    private RegistrarMatricula $registrarMatriculaUseCase;

    public function __construct(
        QueueManager $queueManager,
        RegistrarMatricula $registrarMatriculaUseCase
    ) {
        $this->queueManager = $queueManager;
        $this->registrarMatriculaUseCase = $registrarMatriculaUseCase;
    }

    /**
     * Procesa una solicitud de la cola de matrículas
     */
    public function procesarSiguiente(): ?array
    {
        $job = $this->queueManager->obtenerSiguiente('matriculas');
        if (!$job) {
            return null;
        }

        $jobId = $job['id'];
        $payload = $job['payload'];

        try {
            $dto = RegistrarMatriculaDTO::fromArray($payload);
            $matricula = $this->registrarMatriculaUseCase->ejecutar($dto);

            $this->queueManager->actualizarEstado($jobId, 'COMPLETED');
            return [
                'job_id' => $jobId,
                'status' => 'COMPLETED',
                'matricula' => $matricula->toArray(),
            ];
        } catch (Throwable $e) {
            $this->queueManager->actualizarEstado($jobId, 'FAILED', $e->getMessage());
            return [
                'job_id' => $jobId,
                'status' => 'FAILED',
                'error' => $e->getMessage(),
            ];
        }
    }
}

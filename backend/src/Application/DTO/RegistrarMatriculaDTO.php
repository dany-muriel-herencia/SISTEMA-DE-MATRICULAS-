<?php

declare(strict_types=1);

namespace App\Application\DTO;

use InvalidArgumentException;

final class RegistrarMatriculaDTO
{
    private int $estudianteId;
    private int $periodoId;
    /** @var int[] Array of seccion_ids */
    private array $secciones;

    public function __construct(int $estudianteId, int $periodoId, array $secciones)
    {
        if ($estudianteId <= 0) {
            throw new InvalidArgumentException("El ID del estudiante debe ser un número entero positivo.");
        }
        if ($periodoId <= 0) {
            throw new InvalidArgumentException("El ID del periodo debe ser un número entero positivo.");
        }
        if (empty($secciones)) {
            throw new InvalidArgumentException("Debe seleccionar al menos una sección para matricularse.");
        }

        // Sanitizar array de IDs
        $sanitized = [];
        foreach ($secciones as $sec) {
            $secId = (int)$sec;
            if ($secId <= 0) {
                throw new InvalidArgumentException("Los IDs de sección deben ser números enteros positivos.");
            }
            $sanitized[] = $secId;
        }

        // Eliminar duplicados
        $sanitized = array_unique($sanitized);

        $this->estudianteId = $estudianteId;
        $this->periodoId = $periodoId;
        $this->secciones = array_values($sanitized);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int)($data['estudiante_id'] ?? $data['idEstudiante'] ?? 0),
            (int)($data['periodo_id'] ?? $data['idPeriodo'] ?? 0),
            (array)($data['secciones'] ?? $data['cursos'] ?? [])
        );
    }

    public function getEstudianteId(): int
    {
        return $this->estudianteId;
    }

    public function getPeriodoId(): int
    {
        return $this->periodoId;
    }

    /**
     * @return int[]
     */
    public function getSecciones(): array
    {
        return $this->secciones;
    }
}

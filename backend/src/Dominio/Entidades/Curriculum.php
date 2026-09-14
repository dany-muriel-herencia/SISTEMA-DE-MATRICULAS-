<?php

declare(strict_types=1);

namespace App\Dominio\Entidades;

use InvalidArgumentException;

class Curriculum
{
    private int $idCurriculum;
    private int $idPlan;
    private int $idCurso;
    private int $ciclo;
    private bool $obligatorio;

    public function __construct(
        int $idCurriculum,
        int $idPlan,
        int $idCurso,
        int $ciclo,
        bool $obligatorio
    ) {
        if ($idCurriculum <= 0) {
            throw new InvalidArgumentException(
                'El ID del currículo debe ser mayor que cero'
            );
        }

        if ($idPlan <= 0) {
            throw new InvalidArgumentException(
                'El ID del plan de estudio debe ser mayor que cero'
            );
        }

        if ($idCurso <= 0) {
            throw new InvalidArgumentException(
                'El ID del curso debe ser mayor que cero'
            );
        }

        if ($ciclo <= 0) {
            throw new InvalidArgumentException(
                'El ciclo debe ser mayor que cero'
            );
        }

        $this->idCurriculum = $idCurriculum;
        $this->idPlan = $idPlan;
        $this->idCurso = $idCurso;
        $this->ciclo = $ciclo;
        $this->obligatorio = $obligatorio;
    }

    public function getIdCurriculum(): int
    {
        return $this->idCurriculum;
    }

    public function getIdPlan(): int
    {
        return $this->idPlan;
    }

    public function getIdCurso(): int
    {
        return $this->idCurso;
    }

    public function getCiclo(): int
    {
        return $this->ciclo;
    }

    public function esObligatorio(): bool
    {
        return $this->obligatorio;
    }
}
<?php

namespace App\Dominio\Entidades;

use InvalidArgumentException;

class Curriculum
{
    private int $idCurriculum;
    private string $ciclo;
    private bool $obligatorio;

    public function __construct(
        int $idCurriculum,
        string $ciclo,
        bool $obligatorio
    ) {
        if ($idCurriculum <= 0) {
            throw new InvalidArgumentException(
                'El ID del currículo debe ser mayor que cero'
            );
        }

        if (empty(trim($ciclo))) {
            throw new InvalidArgumentException(
                'El ciclo es obligatorio'
            );
        }

        $this->idCurriculum = $idCurriculum;
        $this->ciclo = $ciclo;
        $this->obligatorio = $obligatorio;
    }

    public function getIdCurriculum(): int
    {
        return $this->idCurriculum;
    }

    public function getCiclo(): string
    {
        return $this->ciclo;
    }

    public function esObligatorio(): bool
    {
        return $this->obligatorio;
    }
}
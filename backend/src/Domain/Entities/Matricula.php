<?php

namespace App\Dominio\Entidades;

use DateTimeImmutable;
use InvalidArgumentException;

class Matricula
{
    private int $idMatricula;
    private DateTimeImmutable $fechaMatricula;
    private string $estado;
    private int $totalCreditos;

    public function __construct(
        int $idMatricula,
        DateTimeImmutable $fechaMatricula,
        string $estado,
        int $totalCreditos
    ) {
        if ($idMatricula <= 0) {
            throw new InvalidArgumentException(
                'El ID de la matrícula debe ser mayor que cero'
            );
        }

        if (empty(trim($estado))) {
            throw new InvalidArgumentException(
                'El estado de la matrícula es obligatorio'
            );
        }

        if ($totalCreditos < 0) {
            throw new InvalidArgumentException(
                'El total de créditos no puede ser negativo'
            );
        }

        $this->idMatricula = $idMatricula;
        $this->fechaMatricula = $fechaMatricula;
        $this->estado = $estado;
        $this->totalCreditos = $totalCreditos;
    }

    public function getIdMatricula(): int
    {
        return $this->idMatricula;
    }

    public function getFechaMatricula(): DateTimeImmutable
    {
        return $this->fechaMatricula;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function getTotalCreditos(): int
    {
        return $this->totalCreditos;
    }
}
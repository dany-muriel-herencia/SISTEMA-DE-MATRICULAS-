<?php

declare(strict_types=1);

namespace App\Dominio\Entidades;

use InvalidArgumentException;

class Prerequisito
{
    private int $idPrerequisito;
    private int $idCurso;
    private int $idCursoRequerido;

    public function __construct(
        int $idPrerequisito,
        int $idCurso,
        int $idCursoRequerido
    ) {
        if ($idPrerequisito <= 0) {
            throw new InvalidArgumentException(
                'El ID del prerrequisito debe ser mayor que cero'
            );
        }

        if ($idCurso <= 0) {
            throw new InvalidArgumentException(
                'El ID del curso debe ser mayor que cero'
            );
        }

        if ($idCursoRequerido <= 0) {
            throw new InvalidArgumentException(
                'El ID del curso requerido debe ser mayor que cero'
            );
        }

        if ($idCurso === $idCursoRequerido) {
            throw new InvalidArgumentException(
                'Un curso no puede ser prerrequisito de sí mismo'
            );
        }

        $this->idPrerequisito = $idPrerequisito;
        $this->idCurso = $idCurso;
        $this->idCursoRequerido = $idCursoRequerido;
    }

    public function getIdPrerequisito(): int
    {
        return $this->idPrerequisito;
    }

    public function getIdCurso(): int
    {
        return $this->idCurso;
    }

    public function getIdCursoRequerido(): int
    {
        return $this->idCursoRequerido;
    }
}
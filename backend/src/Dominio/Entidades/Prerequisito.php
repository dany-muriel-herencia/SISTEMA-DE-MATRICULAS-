<?php

namespace App\Dominio\Entidades;

use InvalidArgumentException;

class Prerequisito
{
    private int $idPrerequisito;

    public function __construct(
        int $idPrerequisito
    ) {
        if ($idPrerequisito <= 0) {
            throw new InvalidArgumentException(
                'El ID del prerrequisito debe ser mayor que cero'
            );
        }

        $this->idPrerequisito = $idPrerequisito;
    }

    public function getIdPrerequisito(): int
    {
        return $this->idPrerequisito;
    }
}
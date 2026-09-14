<?php

declare(strict_types=1);

namespace App\Dominio\Entidades;

use InvalidArgumentException;

class Seccion
{
    private int $idSeccion;
    private int $idCurso;
    private int $idPeriodo;
    private int $idDocente;
    private string $codigo;
    private int $vacantes;
    private int $vacantesDisponibles;

    public function __construct(
        int $idSeccion,
        int $idCurso,
        int $idPeriodo,
        int $idDocente,
        string $codigo,
        int $vacantes,
        int $vacantesDisponibles
    ) {
        if ($idSeccion < 0) {
            throw new InvalidArgumentException('El ID de la sección no puede ser negativo.');
        }

        if ($idCurso <= 0) {
            throw new InvalidArgumentException('El ID del curso debe ser mayor que cero.');
        }

        if ($idPeriodo <= 0) {
            throw new InvalidArgumentException('El ID del periodo debe ser mayor que cero.');
        }

        if ($idDocente <= 0) {
            throw new InvalidArgumentException('El ID del docente debe ser mayor que cero.');
        }

        if (trim($codigo) === '') {
            throw new InvalidArgumentException('El código de la sección no puede estar vacío.');
        }

        if ($vacantes < 0) {
            throw new InvalidArgumentException('La cantidad de vacantes no puede ser negativa.');
        }

        if ($vacantesDisponibles < 0) {
            throw new InvalidArgumentException('Las vacantes disponibles no pueden ser negativas.');
        }

        if ($vacantesDisponibles > $vacantes) {
            throw new InvalidArgumentException(
                'Las vacantes disponibles no pueden ser mayores que las vacantes totales.'
            );
        }

        $this->idSeccion = $idSeccion;
        $this->idCurso = $idCurso;
        $this->idPeriodo = $idPeriodo;
        $this->idDocente = $idDocente;
        $this->codigo = trim($codigo);
        $this->vacantes = $vacantes;
        $this->vacantesDisponibles = $vacantesDisponibles;
    }

    public function getIdSeccion(): int
    {
        return $this->idSeccion;
    }

    public function getIdCurso(): int
    {
        return $this->idCurso;
    }

    public function getIdPeriodo(): int
    {
        return $this->idPeriodo;
    }

    public function getIdDocente(): int
    {
        return $this->idDocente;
    }

    public function getCodigo(): string
    {
        return $this->codigo;
    }

    public function getVacantes(): int
    {
        return $this->vacantes;
    }

    public function getVacantesDisponibles(): int
    {
        return $this->vacantesDisponibles;
    }
}
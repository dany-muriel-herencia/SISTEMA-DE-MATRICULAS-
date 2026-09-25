<?php

namespace App\Dominio\Entidades;

use DateTimeImmutable;
use InvalidArgumentException;

class Horario
{
    private int $idHorario;
    private int $idSeccion;
    private int $idAula;
    private string $diaSemana;
    private DateTimeImmutable $horaInicio;
    private DateTimeImmutable $horaFin;
    private string $modalidad;

    public function __construct(
        int $idHorario,
        int $idSeccion,
        int $idAula,
        string $diaSemana,
        DateTimeImmutable $horaInicio,
        DateTimeImmutable $horaFin,
        string $modalidad
    ) {
        if ($idHorario <= 0) {
            throw new InvalidArgumentException(
                'El ID del horario debe ser mayor que cero'
            );
        }

        if (empty(trim($diaSemana))) {
            throw new InvalidArgumentException(
                'El día de la semana es obligatorio'
            );
        }

        if ($horaFin <= $horaInicio) {
            throw new InvalidArgumentException(
                'La hora de fin debe ser posterior a la hora de inicio'
            );
        }

        if (empty(trim($modalidad))) {
            throw new InvalidArgumentException(
                'La modalidad es obligatoria'
            );
        }

        $this->idHorario = $idHorario;
        $this->idSeccion = $idSeccion;
        $this->idAula = $idAula;
        $this->diaSemana = $diaSemana;
        $this->horaInicio = $horaInicio;
        $this->horaFin = $horaFin;
        $this->modalidad = $modalidad;
    }

    public function getIdHorario(): int
    {
        return $this->idHorario;
    }

    public function getDiaSemana(): string
    {
        return $this->diaSemana;
    }

    public function getHoraInicio(): DateTimeImmutable
    {
        return $this->horaInicio;
    }

    public function getHoraFin(): DateTimeImmutable
    {
        return $this->horaFin;
    }

    public function getModalidad(): string
    {
        return $this->modalidad;
    }
    public function getIdSeccion(): int { return $this->idSeccion; }
    public function getIdAula(): int { return $this->idAula; }

}

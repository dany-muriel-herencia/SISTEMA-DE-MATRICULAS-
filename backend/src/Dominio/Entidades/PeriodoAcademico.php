<?php

namespace App\Dominio\Entidades;

use DateTimeImmutable;
use InvalidArgumentException;

class PeriodoAcademico
{
    private int $idPeriodo;
    private string $nombre;
    private DateTimeImmutable $fechaInicio;
    private DateTimeImmutable $fechaFin;
    private DateTimeImmutable $fechaMatriculaInicio;
    private DateTimeImmutable $fechaMatriculaFin;
    private string $estado;

    public function __construct(
        int $idPeriodo,
        string $nombre,
        DateTimeImmutable $fechaInicio,
        DateTimeImmutable $fechaFin,
        DateTimeImmutable $fechaMatriculaInicio,
        DateTimeImmutable $fechaMatriculaFin,
        string $estado
    ) {
        if ($idPeriodo <= 0) {
            throw new InvalidArgumentException(
                'El ID del periodo debe ser mayor que cero'
            );
        }

        if (empty(trim($nombre))) {
            throw new InvalidArgumentException(
                'El nombre del periodo es obligatorio'
            );
        }

        if ($fechaFin < $fechaInicio) {
            throw new InvalidArgumentException(
                'La fecha de fin no puede ser anterior a la fecha de inicio'
            );
        }

        if ($fechaMatriculaFin < $fechaMatriculaInicio) {
            throw new InvalidArgumentException(
                'La fecha final de matrícula no puede ser anterior a la fecha inicial'
            );
        }

        $this->idPeriodo = $idPeriodo;
        $this->nombre = $nombre;
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->fechaMatriculaInicio = $fechaMatriculaInicio;
        $this->fechaMatriculaFin = $fechaMatriculaFin;
        $this->estado = $estado;
    }

    public function getIdPeriodo(): int
    {
        return $this->idPeriodo;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getFechaInicio(): DateTimeImmutable
    {
        return $this->fechaInicio;
    }

    public function getFechaFin(): DateTimeImmutable
    {
        return $this->fechaFin;
    }

    public function getFechaMatriculaInicio(): DateTimeImmutable
    {
        return $this->fechaMatriculaInicio;
    }

    public function getFechaMatriculaFin(): DateTimeImmutable
    {
        return $this->fechaMatriculaFin;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function toArray(): array {
        return ['id_periodo'=>$this->idPeriodo,'nombre'=>$this->nombre,'estado'=>$this->estado,
            'fecha_inicio'=>$this->fechaInicio->format('Y-m-d'),'fecha_fin'=>$this->fechaFin->format('Y-m-d'),
            'fecha_matricula_inicio'=>$this->fechaMatriculaInicio->format('Y-m-d'),
            'fecha_matricula_fin'=>$this->fechaMatriculaFin->format('Y-m-d')];
    }

}

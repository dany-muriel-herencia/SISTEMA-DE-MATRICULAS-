<?php

namespace App\Dominio\Entidades;

use InvalidArgumentException;

class Curso
{
    private int $idCurso;
    private string $nombre;
    private string $codigo;
    private int $creditos;
    private int $horasTeoria;
    private int $horasPractica;
    private string $ciclo;
    private bool $estado;


    public function __construct(
        int $idCurso,
        string $nombre,
        string $codigo,
        int $creditos,
        int $horasTeoria,
        int $horasPractica,
        string $ciclo,
        bool $estado
    ) {
        if ($idCurso <= 0) {
            throw new InvalidArgumentException(
                'El ID del curso debe ser mayor que cero'
            );
        }

        if (empty(trim($nombre))) {
            throw new InvalidArgumentException(
                'El nombre del curso es obligatorio'
            );
        }

        if (empty(trim($codigo))) {
            throw new InvalidArgumentException(
                'El código del curso es obligatorio'
            );
        }

        if ($creditos <= 0) {
            throw new InvalidArgumentException(
                'Los créditos deben ser mayores que cero'
            );
        }

        $this->idCurso = $idCurso;
        $this->nombre = $nombre;
        $this->codigo = $codigo;
        $this->creditos = $creditos;
        $this->horasTeoria = $horasTeoria;
        $this->horasPractica = $horasPractica;
        $this->ciclo = $ciclo;
        $this->estado = $estado;
    }

    public function getIdCurso(): int
    {
        return $this->idCurso;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getCodigo(): string
    {
        return $this->codigo;
    }

    public function getCreditos(): int
    {
        return $this->creditos;
    }

    public function getHorasTeoria(): int
    {
        return $this->horasTeoria;
    }

    public function getHorasPractica(): int
    {
        return $this->horasPractica;
    }

    public function getCiclo(): string
    {
        return $this->ciclo;
    }

    public function getEstado(): bool
    {
        return $this->estado;
    }
}
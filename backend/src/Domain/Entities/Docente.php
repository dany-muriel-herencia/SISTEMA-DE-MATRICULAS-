<?php

namespace App\Dominio\Entidades;

use DateTimeImmutable;
use InvalidArgumentException;

class Docente extends Usuario
{
    private string $codigo;
    private string $especialidad;
    private string $gradoAcademico;

    public function __construct(
        int $idUsuario,
        string $nombre,
        string $email,
        string $contrasenha,
        string $rol,
        bool $estado,
        DateTimeImmutable $fechaCreacion,
        string $codigo,
        string $especialidad,
        string $gradoAcademico
    ) {
        parent::__construct(
            $idUsuario,
            $nombre,
            $email,
            $contrasenha,
            $rol,
            $estado,
            $fechaCreacion
        );

        if (empty(trim($codigo))) {
            throw new InvalidArgumentException(
                'El código del docente es obligatorio'
            );
        }

        if (empty(trim($especialidad))) {
            throw new InvalidArgumentException(
                'La especialidad es obligatoria'
            );
        }

        if (empty(trim($gradoAcademico))) {
            throw new InvalidArgumentException(
                'El grado académico es obligatorio'
            );
        }

        $this->codigo = $codigo;
        $this->especialidad = $especialidad;
        $this->gradoAcademico = $gradoAcademico;
    }

    public function getCodigo(): string
    {
        return $this->codigo;
    }

    public function getEspecialidad(): string
    {
        return $this->especialidad;
    }

    public function getGradoAcademico(): string
    {
        return $this->gradoAcademico;
    }
}
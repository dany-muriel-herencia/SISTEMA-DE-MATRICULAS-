<?php

namespace App\Dominio\Entidades;

use DateTimeImmutable;
use InvalidArgumentException;

class Estudiante extends Usuario
{
    private string $codigoUniversitario;
    private string $dni;
    private DateTimeImmutable $fechaNacimiento;
    private DateTimeImmutable $fechaIngreso;
    private float $promedioAcademico;

    public function __construct(
        int $idUsuario,
        string $nombre,
        string $email,
        string $contrasenha,
        string $rol,
        bool $estado,
        DateTimeImmutable $fechaCreacion,
        string $codigoUniversitario,
        string $dni,
        DateTimeImmutable $fechaNacimiento,
        DateTimeImmutable $fechaIngreso,
        float $promedioAcademico
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

        if (empty(trim($codigoUniversitario))) {
            throw new InvalidArgumentException(
                'El código universitario es obligatorio'
            );
        }

        if (empty(trim($dni))) {
            throw new InvalidArgumentException(
                'El DNI es obligatorio'
            );
        }

        if ($promedioAcademico < 0 || $promedioAcademico > 20) {
            throw new InvalidArgumentException(
                'El promedio académico debe estar entre 0 y 20'
            );
        }

        $this->codigoUniversitario = $codigoUniversitario;
        $this->dni = $dni;
        $this->fechaNacimiento = $fechaNacimiento;
        $this->fechaIngreso = $fechaIngreso;
        $this->promedioAcademico = $promedioAcademico;
    }

    public function getCodigoUniversitario(): string
    {
        return $this->codigoUniversitario;
    }

    public function getDni(): string
    {
        return $this->dni;
    }

    public function getFechaNacimiento(): DateTimeImmutable
    {
        return $this->fechaNacimiento;
    }

    public function getFechaIngreso(): DateTimeImmutable
    {
        return $this->fechaIngreso;
    }

    public function getPromedioAcademico(): float
    {
        return $this->promedioAcademico;
    }

    public function toArray(): array {
        return parent::toArray() + ['codigo_universitario'=>$this->codigoUniversitario,'dni'=>$this->dni,
            'fecha_nacimiento'=>$this->fechaNacimiento->format('Y-m-d'),'fecha_ingreso'=>$this->fechaIngreso->format('Y-m-d'),
            'promedio_academico'=>$this->promedioAcademico];
    }

}

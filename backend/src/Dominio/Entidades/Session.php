<?php

namespace App\Dominio\Entidades;

use DateTimeImmutable;
use InvalidArgumentException;

class Sesion
{
    private int $idSesion;
    private int $idUsuario;
    private string $token;
    private DateTimeImmutable $fechaInicio;
    private ?DateTimeImmutable $fechaFin;
    private string $ip;
    private bool $activa;

    public function __construct(
        int $idSesion,
        int $idUsuario,
        string $token,
        DateTimeImmutable $fechaInicio,
        ?DateTimeImmutable $fechaFin,
        string $ip,
        bool $activa
    ) {
        if ($idSesion <= 0) {
            throw new InvalidArgumentException(
                'El ID de la sesión debe ser mayor que cero'
            );
        }

        if ($idUsuario <= 0) {
            throw new InvalidArgumentException(
                'El ID del usuario debe ser mayor que cero'
            );
        }

        if (empty(trim($token))) {
            throw new InvalidArgumentException(
                'El token de la sesión es obligatorio'
            );
        }

        if (empty(trim($ip))) {
            throw new InvalidArgumentException(
                'La dirección IP es obligatoria'
            );
        }

        if ($fechaFin !== null && $fechaFin < $fechaInicio) {
            throw new InvalidArgumentException(
                'La fecha de fin no puede ser anterior a la fecha de inicio'
            );
        }

        $this->idSesion = $idSesion;
        $this->token = $token;
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->ip = $ip;
        $this->activa = $activa;
    }

    public function getIdSesion(): int
    {
        return $this->idSesion;
    }
    public function getIdUsuario(): int
    {
        return $this->idUsuario;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getFechaInicio(): DateTimeImmutable
    {
        return $this->fechaInicio;
    }

    public function getFechaFin(): ?DateTimeImmutable
    {
        return $this->fechaFin;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getActiva(): bool
    {
        return $this->activa;
    }
    public function cerrarSesion(): void {
        if(!this->$activa) {
            throw new InvalidArgumentException(
                'La sesión ya está cerrada'
            );
        }
        $this->activa = false;
        $this->fechaFin = new DateTimeImmutable();

    }
}
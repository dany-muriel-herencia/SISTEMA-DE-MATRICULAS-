<?php

namespace App\Dominio\Entidades;

use DateTimeImmutable;
use InvalidArgumentException;

class Auditoria
{
    private int $idAuditoria;
    private string $accion;
    private string $tablaAfectada;
    private DateTimeImmutable $fechaHora;
    private ?string $datosAnteriores;
    private ?string $datosNuevos;
    private string $ip;

    public function __construct(
        int $idAuditoria,
        string $accion,
        string $tablaAfectada,
        DateTimeImmutable $fechaHora,
        ?string $datosAnteriores,
        ?string $datosNuevos,
        string $ip
    ) {
        if ($idAuditoria <= 0) {
            throw new InvalidArgumentException(
                'El ID de auditoría debe ser mayor que cero'
            );
        }

        if (empty(trim($accion))) {
            throw new InvalidArgumentException(
                'La acción de auditoría es obligatoria'
            );
        }

        if (empty(trim($tablaAfectada))) {
            throw new InvalidArgumentException(
                'La tabla afectada es obligatoria'
            );
        }

        if (empty(trim($ip))) {
            throw new InvalidArgumentException(
                'La dirección IP es obligatoria'
            );
        }

        $this->idAuditoria = $idAuditoria;
        $this->accion = $accion;
        $this->tablaAfectada = $tablaAfectada;
        $this->fechaHora = $fechaHora;
        $this->datosAnteriores = $datosAnteriores;
        $this->datosNuevos = $datosNuevos;
        $this->ip = $ip;
    }

    public function getIdAuditoria(): int
    {
        return $this->idAuditoria;
    }

    public function getAccion(): string
    {
        return $this->accion;
    }

    public function getTablaAfectada(): string
    {
        return $this->tablaAfectada;
    }

    public function getFechaHora(): DateTimeImmutable
    {
        return $this->fechaHora;
    }

    public function getDatosAnteriores(): ?string
    {
        return $this->datosAnteriores;
    }

    public function getDatosNuevos(): ?string
    {
        return $this->datosNuevos;
    }

    public function getIp(): string
    {
        return $this->ip;
    }
}
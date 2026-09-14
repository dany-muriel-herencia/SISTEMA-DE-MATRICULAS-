<?php

declare(strict_types=1);

namespace App\Application\DTO;

use InvalidArgumentException;

/**
 * DTO para registrar una entrada de auditoría (CU-56 / CU-57).
 * Captura: usuario, acción, tabla afectada, datos antes/después e IP.
 */
class RegistrarAuditoriaDTO
{
    private int $idUsuario;
    private string $accion;
    private string $tablaAfectada;
    private ?string $datosAnteriores;
    private ?string $datosNuevos;
    private string $ip;

    public function __construct(
        int $idUsuario,
        string $accion,
        string $tablaAfectada,
        ?string $datosAnteriores,
        ?string $datosNuevos,
        string $ip
    ) {
        if ($idUsuario <= 0) {
            throw new InvalidArgumentException('El ID de usuario es obligatorio.');
        }
        if (empty(trim($accion))) {
            throw new InvalidArgumentException('La acción es obligatoria.');
        }
        if (empty(trim($tablaAfectada))) {
            throw new InvalidArgumentException('La tabla afectada es obligatoria.');
        }
        if (empty(trim($ip))) {
            throw new InvalidArgumentException('La IP es obligatoria.');
        }

        $this->idUsuario       = $idUsuario;
        $this->accion          = trim($accion);
        $this->tablaAfectada   = trim($tablaAfectada);
        $this->datosAnteriores = $datosAnteriores;
        $this->datosNuevos     = $datosNuevos;
        $this->ip              = trim($ip);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int) ($data['id_usuario']        ?? 0),
            (string) ($data['accion']         ?? ''),
            (string) ($data['tabla_afectada'] ?? ''),
            isset($data['datos_anteriores']) ? (string) $data['datos_anteriores'] : null,
            isset($data['datos_nuevos'])     ? (string) $data['datos_nuevos']     : null,
            (string) ($data['ip']             ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0')
        );
    }

    public function getIdUsuario(): int       { return $this->idUsuario; }
    public function getAccion(): string       { return $this->accion; }
    public function getTablaAfectada(): string { return $this->tablaAfectada; }
    public function getDatosAnteriores(): ?string { return $this->datosAnteriores; }
    public function getDatosNuevos(): ?string { return $this->datosNuevos; }
    public function getIp(): string           { return $this->ip; }
}

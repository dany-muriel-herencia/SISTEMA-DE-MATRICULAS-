<?php

declare(strict_types=1);

namespace App\Application\DTO;

use InvalidArgumentException;

final class CrearCursoDTO
{
    private string $codigo;
    private string $nombre;
    private int $creditos;
    private int $horasTeoricas;
    private int $horasPracticas;

    public function __construct(
        string $codigo,
        string $nombre,
        int $creditos,
        int $horasTeoricas = 2,
        int $horasPracticas = 2,
        private string $ciclo = '1'
    ) {
        if ($horasTeoricas < 0 || $horasPracticas < 0 || trim($ciclo) === '') throw new InvalidArgumentException('Horas o ciclo inválidos.');
        $codigo = trim($codigo);
        $nombre = trim($nombre);

        if (empty($codigo)) {
            throw new InvalidArgumentException("El código del curso es requerido.");
        }
        if (empty($nombre)) {
            throw new InvalidArgumentException("El nombre del curso es requerido.");
        }
        if ($creditos <= 0) {
            throw new InvalidArgumentException("Los créditos deben ser mayores a cero.");
        }

        $this->codigo = strtoupper($codigo);
        $this->nombre = $nombre;
        $this->creditos = $creditos;
        $this->horasTeoricas = $horasTeoricas;
        $this->horasPracticas = $horasPracticas;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (string)($data['codigo'] ?? ''),
            (string)($data['nombre'] ?? ''),
            (int)($data['creditos'] ?? 0),
            (int)($data['horas_teoria'] ?? $data['horas_teoricas'] ?? 2),
            (int)($data['horas_practica'] ?? $data['horas_practicas'] ?? 2),
            (string)($data['ciclo'] ?? '1')
        );
    }

    public function getCodigo(): string
    {
        return $this->codigo;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getCreditos(): int
    {
        return $this->creditos;
    }

    public function getHorasTeoricas(): int
    {
        return $this->horasTeoricas;
    }

    public function getHorasPracticas(): int
    {
        return $this->horasPracticas;
    }
    public function getCiclo(): string { return $this->ciclo; }

}

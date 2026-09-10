<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use InvalidArgumentException;

class Curso
{
    private ?int $id;
    private string $codigo;
    private string $nombre;
    private int $creditos;
    private int $horasTeoricas;
    private int $horasPracticas;
    private ?string $createdAt;

    public function __construct(
        string $codigo,
        string $nombre,
        int $creditos,
        int $horasTeoricas = 2,
        int $horasPracticas = 2,
        ?int $id = null,
        ?string $createdAt = null
    ) {
        $codigo = trim($codigo);
        $nombre = trim($nombre);
        if (empty($codigo)) {
            throw new InvalidArgumentException("El código del curso no puede estar vacío.");
        }
        if (empty($nombre)) {
            throw new InvalidArgumentException("El nombre del curso no puede estar vacío.");
        }
        if ($creditos <= 0) {
            throw new InvalidArgumentException("Los créditos del curso deben ser mayores a 0.");
        }

        $this->id = $id;
        $this->codigo = $codigo;
        $this->nombre = $nombre;
        $this->creditos = $creditos;
        $this->horasTeoricas = $horasTeoricas;
        $this->horasPracticas = $horasPracticas;
        $this->createdAt = $createdAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
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

    public function getTotalHoras(): int
    {
        return $this->horasTeoricas + $this->horasPracticas;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'creditos' => $this->creditos,
            'horas_teoricas' => $this->horasTeoricas,
            'horas_practicas' => $this->horasPracticas,
            'total_horas' => $this->getTotalHoras(),
            'created_at' => $this->createdAt,
        ];
    }
}

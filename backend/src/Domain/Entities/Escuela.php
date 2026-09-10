<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use InvalidArgumentException;

class Escuela
{
    private ?int $id;
    private int $facultadId;
    private string $codigo;
    private string $nombre;
    private int $duracionSemestres;
    private ?Facultad $facultad;
    private ?string $createdAt;

    public function __construct(
        int $facultadId,
        string $codigo,
        string $nombre,
        int $duracionSemestres = 10,
        ?Facultad $facultad = null,
        ?int $id = null,
        ?string $createdAt = null
    ) {
        $codigo = trim($codigo);
        $nombre = trim($nombre);
        if (empty($codigo)) {
            throw new InvalidArgumentException("El código de la escuela/carrera no puede estar vacío.");
        }
        if (empty($nombre)) {
            throw new InvalidArgumentException("El nombre de la escuela/carrera no puede estar vacío.");
        }
        if ($duracionSemestres <= 0) {
            throw new InvalidArgumentException("La duración en semestres debe ser mayor a 0.");
        }

        $this->id = $id;
        $this->facultadId = $facultadId;
        $this->codigo = $codigo;
        $this->nombre = $nombre;
        $this->duracionSemestres = $duracionSemestres;
        $this->facultad = $facultad;
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

    public function getFacultadId(): int
    {
        return $this->facultadId;
    }

    public function getCodigo(): string
    {
        return $this->codigo;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getDuracionSemestres(): int
    {
        return $this->duracionSemestres;
    }

    public function getFacultad(): ?Facultad
    {
        return $this->facultad;
    }

    public function setFacultad(?Facultad $facultad): void
    {
        $this->facultad = $facultad;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'facultad_id' => $this->facultadId,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'duracion_semestres' => $this->duracionSemestres,
            'facultad' => $this->facultad?->toArray(),
            'created_at' => $this->createdAt,
        ];
    }
}

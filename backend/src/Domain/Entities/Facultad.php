<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use InvalidArgumentException;

class Facultad
{
    private ?int $id;
    private string $codigo;
    private string $nombre;
    private ?string $createdAt;

    public function __construct(
        string $codigo,
        string $nombre,
        ?int $id = null,
        ?string $createdAt = null
    ) {
        $codigo = trim($codigo);
        $nombre = trim($nombre);
        if (empty($codigo)) {
            throw new InvalidArgumentException("El código de la facultad no puede estar vacío.");
        }
        if (empty($nombre)) {
            throw new InvalidArgumentException("El nombre de la facultad no puede estar vacío.");
        }

        $this->id = $id;
        $this->codigo = $codigo;
        $this->nombre = $nombre;
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
            'created_at' => $this->createdAt,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\ValueObjects\Dni;
use App\Domain\ValueObjects\Email;
use InvalidArgumentException;

class Usuario
{
    private ?int $id;
    private int $rolId;
    private Dni $dni;
    private Email $email;
    private string $passwordHash;
    private string $nombre;
    private string $apellido;
    private ?string $telefono;
    private bool $activo;
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(
        int $rolId,
        Dni $dni,
        Email $email,
        string $passwordHash,
        string $nombre,
        string $apellido,
        ?string $telefono = null,
        bool $activo = true,
        ?int $id = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $nombre = trim($nombre);
        $apellido = trim($apellido);
        if (empty($nombre)) {
            throw new InvalidArgumentException("El nombre del usuario no puede estar vacío.");
        }
        if (empty($apellido)) {
            throw new InvalidArgumentException("El apellido del usuario no puede estar vacío.");
        }

        $this->id = $id;
        $this->rolId = $rolId;
        $this->dni = $dni;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->nombre = $nombre;
        $this->apellido = $apellido;
        $this->telefono = $telefono;
        $this->activo = $activo;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getRolId(): int
    {
        return $this->rolId;
    }

    public function getDni(): Dni
    {
        return $this->dni;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getApellido(): string
    {
        return $this->apellido;
    }

    public function getNombreCompleto(): string
    {
        return sprintf('%s %s', $this->nombre, $this->apellido);
    }

    public function getTelefono(): ?string
    {
        return $this->telefono;
    }

    public function isActivo(): bool
    {
        return $this->activo;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function desactivar(): void
    {
        $this->activo = false;
    }

    public function activar(): void
    {
        $this->activo = true;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'rol_id' => $this->rolId,
            'dni' => $this->dni->getValue(),
            'email' => $this->email->getValue(),
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'nombre_completo' => $this->getNombreCompleto(),
            'telefono' => $this->telefono,
            'activo' => $this->activo,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}

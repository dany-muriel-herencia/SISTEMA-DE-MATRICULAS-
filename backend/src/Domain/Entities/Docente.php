<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class Docente
{
    private ?int $id;
    private int $usuarioId;
    private ?string $especialidad;
    private ?string $gradoAcademico;
    private ?Usuario $usuario;
    private ?string $createdAt;

    public function __construct(
        int $usuarioId,
        ?string $especialidad = null,
        ?string $gradoAcademico = null,
        ?Usuario $usuario = null,
        ?int $id = null,
        ?string $createdAt = null
    ) {
        $this->id = $id;
        $this->usuarioId = $usuarioId;
        $this->especialidad = $especialidad;
        $this->gradoAcademico = $gradoAcademico;
        $this->usuario = $usuario;
        $this->createdAt = $createdAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsuarioId(): int
    {
        return $this->usuarioId;
    }

    public function getEspecialidad(): ?string
    {
        return $this->especialidad;
    }

    public function getGradoAcademico(): ?string
    {
        return $this->gradoAcademico;
    }

    public function getUsuario(): ?Usuario
    {
        return $this->usuario;
    }

    public function setUsuario(?Usuario $usuario): void
    {
        $this->usuario = $usuario;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'usuario_id' => $this->usuarioId,
            'especialidad' => $this->especialidad,
            'grado_academico' => $this->gradoAcademico,
            'usuario' => $this->usuario?->toArray(),
            'created_at' => $this->createdAt,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use InvalidArgumentException;

class Estudiante
{
    private ?int $id;
    private int $usuarioId;
    private int $carreraId;
    private int $planEstudioId;
    private string $codigoEstudiante;
    private int $anioIngreso;
    private string $estadoAcademico; // REGULAR, OBSERVADO, EGRESADO, RETIRADO
    private ?Usuario $usuario;
    private ?string $createdAt;
    private ?string $updatedAt;

    public const ESTADOS_VALIDOS = ['REGULAR', 'OBSERVADO', 'EGRESADO', 'RETIRADO'];

    public function __construct(
        int $usuarioId,
        int $carreraId,
        int $planEstudioId,
        string $codigoEstudiante,
        int $anioIngreso,
        string $estadoAcademico = 'REGULAR',
        ?Usuario $usuario = null,
        ?int $id = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $codigoEstudiante = trim($codigoEstudiante);
        if (empty($codigoEstudiante)) {
            throw new InvalidArgumentException("El código del estudiante no puede estar vacío.");
        }
        $estadoAcademico = strtoupper(trim($estadoAcademico));
        if (!in_array($estadoAcademico, self::ESTADOS_VALIDOS, true)) {
            throw new InvalidArgumentException("Estado académico no válido: {$estadoAcademico}");
        }

        $this->id = $id;
        $this->usuarioId = $usuarioId;
        $this->carreraId = $carreraId;
        $this->planEstudioId = $planEstudioId;
        $this->codigoEstudiante = $codigoEstudiante;
        $this->anioIngreso = $anioIngreso;
        $this->estadoAcademico = $estadoAcademico;
        $this->usuario = $usuario;
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

    public function getUsuarioId(): int
    {
        return $this->usuarioId;
    }

    public function getCarreraId(): int
    {
        return $this->carreraId;
    }

    public function getPlanEstudioId(): int
    {
        return $this->planEstudioId;
    }

    public function getCodigoEstudiante(): string
    {
        return $this->codigoEstudiante;
    }

    public function getAnioIngreso(): int
    {
        return $this->anioIngreso;
    }

    public function getEstadoAcademico(): string
    {
        return $this->estadoAcademico;
    }

    public function puedeMatricularse(): bool
    {
        return in_array($this->estadoAcademico, ['REGULAR', 'OBSERVADO'], true);
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

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'usuario_id' => $this->usuarioId,
            'carrera_id' => $this->carreraId,
            'plan_estudio_id' => $this->planEstudioId,
            'codigo_estudiante' => $this->codigoEstudiante,
            'anio_ingreso' => $this->anioIngreso,
            'estado_academico' => $this->estadoAcademico,
            'puede_matricularse' => $this->puedeMatricularse(),
            'usuario' => $this->usuario?->toArray(),
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}

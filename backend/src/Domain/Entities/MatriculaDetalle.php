<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use InvalidArgumentException;

class MatriculaDetalle
{
    private ?int $id;
    private int $matriculaId;
    private int $seccionId;
    private int $creditos;
    private string $estadoCurso; // MATRICULADO, RETIRADO, APROBADO, DESAPROBADO
    private ?Seccion $seccion;

    public const ESTADOS_VALIDOS = ['MATRICULADO', 'RETIRADO', 'APROBADO', 'DESAPROBADO'];

    public function __construct(
        int $matriculaId,
        int $seccionId,
        int $creditos,
        string $estadoCurso = 'MATRICULADO',
        ?Seccion $seccion = null,
        ?int $id = null
    ) {
        $estadoCurso = strtoupper(trim($estadoCurso));
        if (!in_array($estadoCurso, self::ESTADOS_VALIDOS, true)) {
            throw new InvalidArgumentException("Estado del curso no válido: {$estadoCurso}");
        }
        if ($creditos <= 0) {
            throw new InvalidArgumentException("Los créditos deben ser mayores a 0.");
        }

        $this->id = $id;
        $this->matriculaId = $matriculaId;
        $this->seccionId = $seccionId;
        $this->creditos = $creditos;
        $this->estadoCurso = $estadoCurso;
        $this->seccion = $seccion;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getMatriculaId(): int
    {
        return $this->matriculaId;
    }

    public function setMatriculaId(int $matriculaId): void
    {
        $this->matriculaId = $matriculaId;
    }

    public function getSeccionId(): int
    {
        return $this->seccionId;
    }

    public function getCreditos(): int
    {
        return $this->creditos;
    }

    public function getEstadoCurso(): string
    {
        return $this->estadoCurso;
    }

    public function setEstadoCurso(string $estadoCurso): void
    {
        $estadoCurso = strtoupper(trim($estadoCurso));
        if (!in_array($estadoCurso, self::ESTADOS_VALIDOS, true)) {
            throw new InvalidArgumentException("Estado del curso no válido: {$estadoCurso}");
        }
        $this->estadoCurso = $estadoCurso;
    }

    public function getSeccion(): ?Seccion
    {
        return $this->seccion;
    }

    public function setSeccion(?Seccion $seccion): void
    {
        $this->seccion = $seccion;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'matricula_id' => $this->matriculaId,
            'seccion_id' => $this->seccionId,
            'creditos' => $this->creditos,
            'estado_curso' => $this->estadoCurso,
            'seccion' => $this->seccion?->toArray(),
        ];
    }
}

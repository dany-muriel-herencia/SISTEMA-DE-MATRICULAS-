<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use InvalidArgumentException;

class Seccion
{
    private ?int $id;
    private int $periodoId;
    private int $cursoId;
    private ?int $docenteId;
    private string $letraSeccion;
    private int $capacidadMaxima;
    private int $vacantesDisponibles;
    private ?Curso $curso;
    private ?Usuario $docente;
    private array $horarios; // Array de horarios asociados
    private ?string $createdAt;

    public function __construct(
        int $periodoId,
        int $cursoId,
        string $letraSeccion,
        int $capacidadMaxima = 40,
        int $vacantesDisponibles = 40,
        ?int $docenteId = null,
        ?Curso $curso = null,
        ?Usuario $docente = null,
        array $horarios = [],
        ?int $id = null,
        ?string $createdAt = null
    ) {
        $letraSeccion = strtoupper(trim($letraSeccion));
        if (empty($letraSeccion)) {
            throw new InvalidArgumentException("La letra de sección no puede estar vacía.");
        }
        if ($capacidadMaxima <= 0) {
            throw new InvalidArgumentException("La capacidad máxima debe ser mayor a 0.");
        }

        $this->id = $id;
        $this->periodoId = $periodoId;
        $this->cursoId = $cursoId;
        $this->letraSeccion = $letraSeccion;
        $this->capacidadMaxima = $capacidadMaxima;
        $this->vacantesDisponibles = $vacantesDisponibles;
        $this->docenteId = $docenteId;
        $this->curso = $curso;
        $this->docente = $docente;
        $this->horarios = $horarios;
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

    public function getPeriodoId(): int
    {
        return $this->periodoId;
    }

    public function getCursoId(): int
    {
        return $this->cursoId;
    }

    public function getDocenteId(): ?int
    {
        return $this->docenteId;
    }

    public function getLetraSeccion(): string
    {
        return $this->letraSeccion;
    }

    public function getCapacidadMaxima(): int
    {
        return $this->capacidadMaxima;
    }

    public function getVacantesDisponibles(): int
    {
        return $this->vacantesDisponibles;
    }

    public function tieneVacantes(): bool
    {
        return $this->vacantesDisponibles > 0;
    }

    public function decrementarVacante(): void
    {
        if ($this->vacantesDisponibles <= 0) {
            throw new InvalidArgumentException("No hay vacantes disponibles para la sección {$this->letraSeccion}.");
        }
        $this->vacantesDisponibles--;
    }

    public function incrementarVacante(): void
    {
        if ($this->vacantesDisponibles < $this->capacidadMaxima) {
            $this->vacantesDisponibles++;
        }
    }

    public function getCurso(): ?Curso
    {
        return $this->curso;
    }

    public function setCurso(?Curso $curso): void
    {
        $this->curso = $curso;
    }

    public function getDocente(): ?Usuario
    {
        return $this->docente;
    }

    public function setDocente(?Usuario $docente): void
    {
        $this->docente = $docente;
    }

    public function getHorarios(): array
    {
        return $this->horarios;
    }

    public function setHorarios(array $horarios): void
    {
        $this->horarios = $horarios;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'periodo_id' => $this->periodoId,
            'curso_id' => $this->cursoId,
            'docente_id' => $this->docenteId,
            'letra_seccion' => $this->letraSeccion,
            'capacidad_maxima' => $this->capacidadMaxima,
            'vacantes_disponibles' => $this->vacantesDisponibles,
            'tiene_vacantes' => $this->tieneVacantes(),
            'curso' => $this->curso?->toArray(),
            'docente' => $this->docente?->toArray(),
            'horarios' => $this->horarios,
            'created_at' => $this->createdAt,
        ];
    }
}

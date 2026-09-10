<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use DateTimeImmutable;
use InvalidArgumentException;

class PeriodoAcademico
{
    private ?int $id;
    private string $codigo;
    private int $anio;
    private string $semestre; // I, II, EXTRAORDINARIO
    private string $fechaInicio;
    private string $fechaFin;
    private string $fechaInicioMatricula;
    private string $fechaFinMatricula;
    private string $estado; // PLANIFICACION, MATRICULA_ABIERTA, EN_CURSO, CERRADO
    private ?string $createdAt;

    public const SEMESTRES_VALIDOS = ['I', 'II', 'EXTRAORDINARIO'];
    public const ESTADOS_VALIDOS = ['PLANIFICACION', 'MATRICULA_ABIERTA', 'EN_CURSO', 'CERRADO'];

    public function __construct(
        string $codigo,
        int $anio,
        string $semestre,
        string $fechaInicio,
        string $fechaFin,
        string $fechaInicioMatricula,
        string $fechaFinMatricula,
        string $estado = 'PLANIFICACION',
        ?int $id = null,
        ?string $createdAt = null
    ) {
        $codigo = trim($codigo);
        $semestre = strtoupper(trim($semestre));
        $estado = strtoupper(trim($estado));

        if (empty($codigo)) {
            throw new InvalidArgumentException("El código del periodo académico no puede estar vacío.");
        }
        if (!in_array($semestre, self::SEMESTRES_VALIDOS, true)) {
            throw new InvalidArgumentException("Semestre no válido: {$semestre}");
        }
        if (!in_array($estado, self::ESTADOS_VALIDOS, true)) {
            throw new InvalidArgumentException("Estado no válido: {$estado}");
        }

        $this->id = $id;
        $this->codigo = $codigo;
        $this->anio = $anio;
        $this->semestre = $semestre;
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->fechaInicioMatricula = $fechaInicioMatricula;
        $this->fechaFinMatricula = $fechaFinMatricula;
        $this->estado = $estado;
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

    public function getAnio(): int
    {
        return $this->anio;
    }

    public function getSemestre(): string
    {
        return $this->semestre;
    }

    public function getFechaInicio(): string
    {
        return $this->fechaInicio;
    }

    public function getFechaFin(): string
    {
        return $this->fechaFin;
    }

    public function getFechaInicioMatricula(): string
    {
        return $this->fechaInicioMatricula;
    }

    public function getFechaFinMatricula(): string
    {
        return $this->fechaFinMatricula;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function estaEnPeriodoMatricula(): bool
    {
        if ($this->estado !== 'MATRICULA_ABIERTA') {
            return false;
        }

        $ahora = new DateTimeImmutable();
        $inicio = new DateTimeImmutable($this->fechaInicioMatricula);
        $fin = new DateTimeImmutable($this->fechaFinMatricula);

        return $ahora >= $inicio && $ahora <= $fin;
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
            'anio' => $this->anio,
            'semestre' => $this->semestre,
            'fecha_inicio' => $this->fechaInicio,
            'fecha_fin' => $this->fechaFin,
            'fecha_inicio_matricula' => $this->fechaInicioMatricula,
            'fecha_fin_matricula' => $this->fechaFinMatricula,
            'estado' => $this->estado,
            'matricula_abierta' => $this->estaEnPeriodoMatricula(),
            'created_at' => $this->createdAt,
        ];
    }
}

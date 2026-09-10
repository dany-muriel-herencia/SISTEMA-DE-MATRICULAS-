<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\ValueObjects\CodigoMatricula;
use InvalidArgumentException;

class Matricula
{
    private ?int $id;
    private int $estudianteId;
    private int $periodoId;
    private CodigoMatricula $codigoMatricula;
    private string $fechaMatricula;
    private int $totalCreditos;
    private string $estado; // REGISTRADA, RECTIFICADA, ANULADA
    /** @var MatriculaDetalle[] */
    private array $detalles;
    private ?Estudiante $estudiante;
    private ?PeriodoAcademico $periodo;
    private ?string $createdAt;
    private ?string $updatedAt;

    public const ESTADOS_VALIDOS = ['REGISTRADA', 'RECTIFICADA', 'ANULADA'];
    public const MAX_CREDITOS_PERMITIDOS = 26;
    public const MIN_CREDITOS_PERMITIDOS = 1;

    public function __construct(
        int $estudianteId,
        int $periodoId,
        CodigoMatricula $codigoMatricula,
        string $fechaMatricula,
        int $totalCreditos = 0,
        string $estado = 'REGISTRADA',
        array $detalles = [],
        ?Estudiante $estudiante = null,
        ?PeriodoAcademico $periodo = null,
        ?int $id = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $estado = strtoupper(trim($estado));
        if (!in_array($estado, self::ESTADOS_VALIDOS, true)) {
            throw new InvalidArgumentException("Estado de matrícula no válido: {$estado}");
        }

        $this->id = $id;
        $this->estudianteId = $estudianteId;
        $this->periodoId = $periodoId;
        $this->codigoMatricula = $codigoMatricula;
        $this->fechaMatricula = $fechaMatricula;
        $this->totalCreditos = $totalCreditos;
        $this->estado = $estado;
        $this->detalles = $detalles;
        $this->estudiante = $estudiante;
        $this->periodo = $periodo;
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

    public function getEstudianteId(): int
    {
        return $this->estudianteId;
    }

    public function getPeriodoId(): int
    {
        return $this->periodoId;
    }

    public function getCodigoMatricula(): CodigoMatricula
    {
        return $this->codigoMatricula;
    }

    public function getFechaMatricula(): string
    {
        return $this->fechaMatricula;
    }

    public function getTotalCreditos(): int
    {
        return $this->totalCreditos;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function esValida(): bool
    {
        return $this->estado === 'REGISTRADA' || $this->estado === 'RECTIFICADA';
    }

    public function anular(): void
    {
        if ($this->estado === 'ANULADA') {
            throw new InvalidArgumentException("La matrícula ya se encuentra anulada.");
        }
        $this->estado = 'ANULADA';
        foreach ($this->detalles as $detalle) {
            $detalle->setEstadoCurso('RETIRADO');
        }
    }

    /**
     * @return MatriculaDetalle[]
     */
    public function getDetalles(): array
    {
        return $this->detalles;
    }

    public function agregarDetalle(MatriculaDetalle $detalle): void
    {
        $this->detalles[] = $detalle;
        $this->recalcularCreditos();
    }

    public function setDetalles(array $detalles): void
    {
        $this->detalles = $detalles;
        $this->recalcularCreditos();
    }

    public function recalcularCreditos(): void
    {
        $total = 0;
        foreach ($this->detalles as $detalle) {
            if ($detalle->getEstadoCurso() === 'MATRICULADO') {
                $total += $detalle->getCreditos();
            }
        }
        $this->totalCreditos = $total;
    }

    public function validarLimiteCreditos(int $maxCreditos = self::MAX_CREDITOS_PERMITIDOS): bool
    {
        return $this->totalCreditos <= $maxCreditos && $this->totalCreditos >= self::MIN_CREDITOS_PERMITIDOS;
    }

    public function getEstudiante(): ?Estudiante
    {
        return $this->estudiante;
    }

    public function setEstudiante(?Estudiante $estudiante): void
    {
        $this->estudiante = $estudiante;
    }

    public function getPeriodo(): ?PeriodoAcademico
    {
        return $this->periodo;
    }

    public function setPeriodo(?PeriodoAcademico $periodo): void
    {
        $this->periodo = $periodo;
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
            'estudiante_id' => $this->estudianteId,
            'periodo_id' => $this->periodoId,
            'codigo_matricula' => $this->codigoMatricula->getValue(),
            'fecha_matricula' => $this->fechaMatricula,
            'total_creditos' => $this->totalCreditos,
            'estado' => $this->estado,
            'estudiante' => $this->estudiante?->toArray(),
            'periodo' => $this->periodo?->toArray(),
            'detalles' => array_map(fn(MatriculaDetalle $d) => $d->toArray(), $this->detalles),
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}

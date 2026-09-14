<?php

declare(strict_types=1);

namespace App\Dominio\Entidades;

use DateTimeImmutable;
use InvalidArgumentException;
use App\Dominio\ValueObjects\CodigoMatricula;

class Matricula
{
    private int $idMatricula;
    private int $idEstudiante;
    private int $idPeriodo;
    private DateTimeImmutable $fechaMatricula;
    private string $estado;
    private int $totalCreditos;
    private ?CodigoMatricula $codigoMatricula = null;
    /** @var DetalleMatricula[] */
    private array $detalles = [];

    public function __construct(
        int $idMatricula,
        int $idEstudiante,
        int $idPeriodo,
        DateTimeImmutable $fechaMatricula,
        string $estado,
        int $totalCreditos
    ) {
        if ($idMatricula < 0) {
            throw new InvalidArgumentException(
                'El ID de la matrícula no puede ser negativo'
            );
        }

        if ($idEstudiante <= 0) {
            throw new InvalidArgumentException(
                'El ID del estudiante debe ser mayor que cero'
            );
        }

        if ($idPeriodo <= 0) {
            throw new InvalidArgumentException(
                'El ID del periodo debe ser mayor que cero'
            );
        }

        if (empty(trim($estado))) {
            throw new InvalidArgumentException(
                'El estado de la matrícula es obligatorio'
            );
        }

        if ($totalCreditos < 0) {
            throw new InvalidArgumentException(
                'El total de créditos no puede ser negativo'
            );
        }

        $this->idMatricula = $idMatricula;
        $this->idEstudiante = $idEstudiante;
        $this->idPeriodo = $idPeriodo;
        $this->fechaMatricula = $fechaMatricula;
        $this->estado = $estado;
        $this->totalCreditos = $totalCreditos;
    }

    public function getIdMatricula(): int
    {
        return $this->idMatricula;
    }

    public function setIdMatricula(int $idMatricula): void
    {
        if ($idMatricula <= 0) {
            throw new InvalidArgumentException(
                'El ID de la matrícula debe ser mayor que cero'
            );
        }

        $this->idMatricula = $idMatricula;
    }

    public function getIdEstudiante(): int
    {
        return $this->idEstudiante;
    }

    public function getIdPeriodo(): int
    {
        return $this->idPeriodo;
    }

    public function getFechaMatricula(): DateTimeImmutable
    {
        return $this->fechaMatricula;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function getTotalCreditos(): int
    {
        return $this->totalCreditos;
    }

    public function setCodigoMatricula(CodigoMatricula $codigoMatricula): void
    {
        $this->codigoMatricula = $codigoMatricula;
    }

    public function getCodigoMatricula(): ?CodigoMatricula
    {
        return $this->codigoMatricula;
    }

    /** @param DetalleMatricula[] $detalles */
    public function setDetalles(array $detalles): void
    {
        $this->detalles = $detalles;
    }

    /** @return DetalleMatricula[] */
    public function getDetalles(): array
    {
        return $this->detalles;
    }

    public function esValida(): bool
    {
        return $this->estado === 'REGISTRADA';
    }

    public function anular(): void
    {
        $this->estado = 'ANULADA';
    }

    public function toArray(): array
    {
        return [
            'id_matricula' => $this->idMatricula,
            'id_estudiante' => $this->idEstudiante,
            'id_periodo' => $this->idPeriodo,
            'codigo_matricula' => $this->codigoMatricula?->getValue(),
            'fecha_matricula' => $this->fechaMatricula->format('Y-m-d H:i:s'),
            'estado' => $this->estado,
            'total_creditos' => $this->totalCreditos,
            'detalles' => array_map(
                static fn(DetalleMatricula $detalle): array => $detalle->toArray(),
                $this->detalles
            ),
        ];
    }
}
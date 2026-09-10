<?php

declare(strict_types=1);

namespace App\Application\DTO;

use InvalidArgumentException;

final class CrearEstudianteDTO
{
    private int $usuarioId;
    private int $carreraId;
    private int $planEstudioId;
    private string $codigoEstudiante;
    private int $anioIngreso;
    private string $estadoAcademico;

    public function __construct(
        int $usuarioId,
        int $carreraId,
        int $planEstudioId,
        string $codigoEstudiante,
        int $anioIngreso,
        string $estadoAcademico = 'REGULAR'
    ) {
        if ($usuarioId <= 0 || $carreraId <= 0 || $planEstudioId <= 0) {
            throw new InvalidArgumentException("Los IDs de usuario, carrera y plan de estudio deben ser enteros positivos.");
        }
        if (empty(trim($codigoEstudiante))) {
            throw new InvalidArgumentException("El código de estudiante es obligatorio.");
        }

        $this->usuarioId = $usuarioId;
        $this->carreraId = $carreraId;
        $this->planEstudioId = $planEstudioId;
        $this->codigoEstudiante = trim($codigoEstudiante);
        $this->anioIngreso = $anioIngreso > 0 ? $anioIngreso : (int)date('Y');
        $this->estadoAcademico = strtoupper(trim($estadoAcademico));
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int)($data['usuario_id'] ?? 0),
            (int)($data['carrera_id'] ?? 0),
            (int)($data['plan_estudio_id'] ?? 0),
            (string)($data['codigo_estudiante'] ?? $data['codigo'] ?? ''),
            (int)($data['anio_ingreso'] ?? date('Y')),
            (string)($data['estado_academico'] ?? 'REGULAR')
        );
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
}

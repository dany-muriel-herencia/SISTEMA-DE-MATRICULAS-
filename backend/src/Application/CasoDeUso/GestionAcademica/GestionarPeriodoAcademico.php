<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\GestionAcademica;

use App\Dominio\Entidades\PeriodoAcademico;
use App\Dominio\Repositorios\PeriodoAcademicoRepositorio;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * CU-18: Registrar periodos académicos y fechas de matrícula.
 * Actor Principal: Administrador.
 */
class GestionarPeriodoAcademico
{
    private PeriodoAcademicoRepositorio $periodoRepo;

    public function __construct(PeriodoAcademicoRepositorio $periodoRepo)
    {
        $this->periodoRepo = $periodoRepo;
    }

    public function registrarPeriodo(
        string $nombre,
        string $fechaInicio,
        string $fechaFin,
        string $fechaMatriculaInicio,
        string $fechaMatriculaFin,
        string $estado = 'INACTIVO'
    ): array {
        if (empty(trim($nombre))) {
            throw new InvalidArgumentException('El nombre del periodo académico es obligatorio.');
        }

        $fInicio    = new DateTimeImmutable($fechaInicio);
        $fFin       = new DateTimeImmutable($fechaFin);
        $fMatInicio = new DateTimeImmutable($fechaMatriculaInicio);
        $fMatFin    = new DateTimeImmutable($fechaMatriculaFin);

        // Fictional ID 1 for instantiation before DB insert
        $periodo = new PeriodoAcademico(
            1,
            $nombre,
            $fInicio,
            $fFin,
            $fMatInicio,
            $fMatFin,
            strtoupper($estado)
        );

        $this->periodoRepo->guardar($periodo);

        return [
            'nombre'                 => $nombre,
            'fecha_inicio'           => $fInicio->format('Y-m-d'),
            'fecha_fin'              => $fFin->format('Y-m-d'),
            'fecha_matricula_inicio' => $fMatInicio->format('Y-m-d'),
            'fecha_matricula_fin'    => $fMatFin->format('Y-m-d'),
            'estado'                 => strtoupper($estado),
            'mensaje'                => 'Periodo académico y ventana de matrícula registrados correctamente.'
        ];
    }

    public function listarPeriodos(): array
    {
        $periodos = $this->periodoRepo->listar();
        return array_map(fn($p) => [
            'id_periodo'             => $p->getIdPeriodo(),
            'nombre'                 => $p->getNombre(),
            'fecha_inicio'           => $p->getFechaInicio()->format('Y-m-d'),
            'fecha_fin'              => $p->getFechaFin()->format('Y-m-d'),
            'fecha_matricula_inicio' => $p->getFechaMatriculaInicio()->format('Y-m-d'),
            'fecha_matricula_fin'    => $p->getFechaMatriculaFin()->format('Y-m-d'),
            'estado'                 => $p->getEstado()
        ], $periodos);
    }
}

<?php

declare(strict_types=1);

namespace App\Presentacion\Controladores;

use App\Dominio\Entidades\PeriodoAcademico;
use App\Dominio\Repositorios\PeriodoAcademicoRepositorio;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use Throwable;

final class PeriodoAcademicoControlador
{
    public function __construct(
        private readonly PeriodoAcademicoRepositorio $periodoRepositorio
    ) {}

    public function registrar(array $datos): array
    {
        try {
            $nombre      = trim((string)($datos['nombre'] ?? ''));
            $fechaInicio = new DateTimeImmutable((string)($datos['fecha_inicio'] ?? 'now'));
            $fechaFin    = new DateTimeImmutable((string)($datos['fecha_fin'] ?? '+4 months'));
            $matInicio   = new DateTimeImmutable((string)($datos['fecha_matricula_inicio'] ?? $datos['fecha_inicio'] ?? 'now'));
            $matFin      = new DateTimeImmutable((string)($datos['fecha_matricula_fin'] ?? '+2 weeks'));
            $estado      = strtoupper(trim((string)($datos['estado'] ?? 'ACTIVO')));

            $periodo = new PeriodoAcademico(
                1,
                $nombre,
                $fechaInicio,
                $fechaFin,
                $matInicio,
                $matFin,
                $estado
            );

            $this->periodoRepositorio->guardar($periodo);

            return [
                'success' => true,
                'message' => 'Periodo académico registrado correctamente.',
                'data'    => $this->mapPeriodo($periodo)
            ];
        } catch (InvalidArgumentException | DomainException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al registrar periodo académico: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function buscarPorId(int $idPeriodo): array
    {
        try {
            $periodo = $this->periodoRepositorio->buscarPorId($idPeriodo);
            if ($periodo === null) {
                return [
                    'success' => false,
                    'message' => "Periodo académico con ID {$idPeriodo} no encontrado.",
                    'data'    => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Periodo académico encontrado.',
                'data'    => $this->mapPeriodo($periodo)
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al buscar periodo académico: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function obtenerPeriodoActivo(): array
    {
        try {
            $periodo = $this->periodoRepositorio->obtenerPeriodoActivo();
            if ($periodo === null) {
                return [
                    'success' => false,
                    'message' => 'No hay ningún periodo académico activo actualmente.',
                    'data'    => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Periodo académico activo obtenido.',
                'data'    => $this->mapPeriodo($periodo)
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener periodo activo: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function listar(): array
    {
        try {
            $periodos = $this->periodoRepositorio->listar();
            $data = array_map(fn(PeriodoAcademico $p) => $this->mapPeriodo($p), $periodos);

            return [
                'success' => true,
                'message' => 'Periodos académicos obtenidos correctamente.',
                'data'    => $data
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al listar periodos académicos: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function actualizar(array $datos): array
    {
        try {
            $idPeriodo = (int)($datos['id_periodo'] ?? $datos['id'] ?? 0);
            $existente = $this->periodoRepositorio->buscarPorId($idPeriodo);
            if ($existente === null) {
                return [
                    'success' => false,
                    'message' => "Periodo académico con ID {$idPeriodo} no encontrado.",
                    'data'    => null
                ];
            }

            $nombre      = trim((string)($datos['nombre'] ?? $existente->getNombre()));
            $fechaInicio = isset($datos['fecha_inicio']) ? new DateTimeImmutable((string)$datos['fecha_inicio']) : $existente->getFechaInicio();
            $fechaFin    = isset($datos['fecha_fin']) ? new DateTimeImmutable((string)$datos['fecha_fin']) : $existente->getFechaFin();
            $matInicio   = isset($datos['fecha_matricula_inicio']) ? new DateTimeImmutable((string)$datos['fecha_matricula_inicio']) : $existente->getFechaMatriculaInicio();
            $matFin      = isset($datos['fecha_matricula_fin']) ? new DateTimeImmutable((string)$datos['fecha_matricula_fin']) : $existente->getFechaMatriculaFin();
            $estado      = strtoupper(trim((string)($datos['estado'] ?? $existente->getEstado())));

            $actualizado = new PeriodoAcademico(
                $idPeriodo,
                $nombre,
                $fechaInicio,
                $fechaFin,
                $matInicio,
                $matFin,
                $estado
            );

            $this->periodoRepositorio->actualizar($actualizado);

            return [
                'success' => true,
                'message' => 'Periodo académico actualizado correctamente.',
                'data'    => $this->mapPeriodo($actualizado)
            ];
        } catch (InvalidArgumentException | DomainException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al actualizar periodo académico: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    private function mapPeriodo(PeriodoAcademico $p): array
    {
        return [
            'id_periodo'              => $p->getIdPeriodo(),
            'nombre'                  => $p->getNombre(),
            'fecha_inicio'            => $p->getFechaInicio()->format('Y-m-d'),
            'fecha_fin'               => $p->getFechaFin()->format('Y-m-d'),
            'fecha_matricula_inicio'  => $p->getFechaMatriculaInicio()->format('Y-m-d'),
            'fecha_matricula_fin'     => $p->getFechaMatriculaFin()->format('Y-m-d'),
            'estado'                  => $p->getEstado()
        ];
    }
}

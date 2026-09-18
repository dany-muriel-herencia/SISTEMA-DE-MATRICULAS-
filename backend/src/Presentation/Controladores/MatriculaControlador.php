<?php

declare(strict_types=1);

namespace App\Presentacion\Controladores;

use App\Dominio\Entidades\Matricula;
use App\Dominio\Repositorios\MatriculaRepositorio;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use Throwable;

final class MatriculaControlador
{
    public function __construct(
        private readonly MatriculaRepositorio $matriculaRepositorio
    ) {}

    public function registrar(array $datos): array
    {
        try {
            $idEstudiante  = (int)($datos['id_estudiante'] ?? 0);
            $idPeriodo     = (int)($datos['id_periodo'] ?? 0);
            $totalCreditos = (int)($datos['total_creditos'] ?? 0);
            $estado        = strtoupper(trim((string)($datos['estado'] ?? 'REGISTRADA')));

            $matricula = new Matricula(
                0,
                $idEstudiante,
                $idPeriodo,
                new DateTimeImmutable(),
                $estado,
                $totalCreditos
            );

            if (!empty($datos['codigo_matricula'])) {
                $matricula->setCodigoMatricula($datos['codigo_matricula']);
            }

            $this->matriculaRepositorio->guardar($matricula);

            return [
                'success' => true,
                'message' => 'Matrícula registrada correctamente.',
                'data'    => $this->mapMatricula($matricula)
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
                'message' => 'Error al registrar matrícula: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function buscarPorId(int $idMatricula): array
    {
        try {
            $matricula = $this->matriculaRepositorio->buscarPorId($idMatricula);
            if ($matricula === null) {
                return [
                    'success' => false,
                    'message' => "Matrícula con ID {$idMatricula} no encontrada.",
                    'data'    => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Matrícula encontrada.',
                'data'    => $this->mapMatricula($matricula)
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al buscar matrícula: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function buscarPorCodigo(string $codigo): array
    {
        try {
            $matricula = $this->matriculaRepositorio->buscarPorCodigo($codigo);
            if ($matricula === null) {
                return [
                    'success' => false,
                    'message' => "Matrícula con código {$codigo} no encontrada.",
                    'data'    => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Matrícula encontrada.',
                'data'    => $this->mapMatricula($matricula)
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al buscar matrícula por código: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function listarPorEstudiante(int $idEstudiante): array
    {
        try {
            $matriculas = $this->matriculaRepositorio->listarPorEstudiante($idEstudiante);
            $data = array_map(fn(Matricula $m) => $this->mapMatricula($m), $matriculas);

            return [
                'success' => true,
                'message' => 'Matrículas del estudiante obtenidas correctamente.',
                'data'    => $data
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al listar matrículas del estudiante: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function listarPorPeriodo(int $idPeriodo): array
    {
        try {
            $matriculas = $this->matriculaRepositorio->buscarPorPeriodo($idPeriodo);
            $data = array_map(fn(Matricula $m) => $this->mapMatricula($m), $matriculas);

            return [
                'success' => true,
                'message' => 'Matrículas del periodo obtenidas correctamente.',
                'data'    => $data
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al listar matrículas del periodo: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function actualizar(array $datos): array
    {
        try {
            $idMatricula = (int)($datos['id_matricula'] ?? $datos['id'] ?? 0);
            $existente   = $this->matriculaRepositorio->buscarPorId($idMatricula);
            if ($existente === null) {
                return [
                    'success' => false,
                    'message' => "Matrícula con ID {$idMatricula} no encontrada.",
                    'data'    => null
                ];
            }

            $idEstudiante  = (int)($datos['id_estudiante'] ?? $existente->getIdEstudiante());
            $idPeriodo     = (int)($datos['id_periodo'] ?? $existente->getIdPeriodo());
            $estado        = strtoupper(trim((string)($datos['estado'] ?? $existente->getEstado())));
            $totalCreditos = isset($datos['total_creditos']) ? (int)$datos['total_creditos'] : $existente->getTotalCreditos();

            $actualizada = new Matricula(
                $idMatricula,
                $idEstudiante,
                $idPeriodo,
                $existente->getFechaMatricula(),
                $estado,
                $totalCreditos
            );

            if ($existente->getCodigoMatricula() !== null) {
                $actualizada->setCodigoMatricula($existente->getCodigoMatricula());
            }

            $this->matriculaRepositorio->actualizar($actualizada);

            return [
                'success' => true,
                'message' => 'Matrícula actualizada correctamente.',
                'data'    => $this->mapMatricula($actualizada)
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
                'message' => 'Error al actualizar matrícula: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    private function mapMatricula(Matricula $m): array
    {
        return [
            'id_matricula'     => $m->getIdMatricula(),
            'id_estudiante'    => $m->getIdEstudiante(),
            'id_periodo'       => $m->getIdPeriodo(),
            'fecha_matricula'  => $m->getFechaMatricula()->format('Y-m-d H:i:s'),
            'estado'           => $m->getEstado(),
            'total_creditos'   => $m->getTotalCreditos(),
            'codigo_matricula' => $m->getCodigoMatricula()?->getValue()
        ];
    }
}

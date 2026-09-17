<?php

declare(strict_types=1);

namespace App\Presentacion\Controladores;

use App\Dominio\Entidades\Seccion;
use App\Dominio\Repositorios\SeccionRepositorio;
use DomainException;
use InvalidArgumentException;
use Throwable;

final class SeccionControlador
{
    public function __construct(
        private readonly SeccionRepositorio $seccionRepositorio
    ) {}

    public function registrar(array $datos): array
    {
        try {
            $idCurso   = (int)($datos['id_curso'] ?? 0);
            $idPeriodo = (int)($datos['id_periodo'] ?? 0);
            $idDocente = (int)($datos['id_docente'] ?? 0);
            $codigo    = trim((string)($datos['codigo'] ?? ''));
            $vacantes  = (int)($datos['vacantes'] ?? 30);
            $disponibles = (int)($datos['vacantes_disponibles'] ?? $vacantes);

            $seccion = new Seccion(
                0,
                $idCurso,
                $idPeriodo,
                $idDocente,
                $codigo,
                $vacantes,
                $disponibles
            );

            $this->seccionRepositorio->guardar($seccion);

            return [
                'success' => true,
                'message' => 'Sección registrada correctamente.',
                'data'    => $this->mapSeccion($seccion)
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
                'message' => 'Error al registrar sección: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function buscarPorId(int $idSeccion): array
    {
        try {
            $seccion = $this->seccionRepositorio->buscarPorId($idSeccion);
            if ($seccion === null) {
                return [
                    'success' => false,
                    'message' => "Sección con ID {$idSeccion} no encontrada.",
                    'data'    => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Sección encontrada.',
                'data'    => $this->mapSeccion($seccion)
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al buscar sección: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function listar(): array
    {
        try {
            $secciones = $this->seccionRepositorio->listarDisponibles();
            $data = array_map(fn(Seccion $s) => $this->mapSeccion($s), $secciones);

            return [
                'success' => true,
                'message' => 'Secciones disponibles obtenidas correctamente.',
                'data'    => $data
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al listar secciones: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function actualizar(array $datos): array
    {
        try {
            $idSeccion = (int)($datos['id_seccion'] ?? $datos['id'] ?? 0);
            $existente = $this->seccionRepositorio->buscarPorId($idSeccion);
            if ($existente === null) {
                return [
                    'success' => false,
                    'message' => "Sección con ID {$idSeccion} no encontrada.",
                    'data'    => null
                ];
            }

            $idCurso   = (int)($datos['id_curso'] ?? $existente->getIdCurso());
            $idPeriodo = (int)($datos['id_periodo'] ?? $existente->getIdPeriodo());
            $idDocente = (int)($datos['id_docente'] ?? $existente->getIdDocente());
            $codigo    = trim((string)($datos['codigo'] ?? $existente->getCodigo()));
            $vacantes  = isset($datos['vacantes']) ? (int)$datos['vacantes'] : $existente->getVacantes();
            $disponibles = isset($datos['vacantes_disponibles']) ? (int)$datos['vacantes_disponibles'] : $existente->getVacantesDisponibles();

            $actualizada = new Seccion(
                $idSeccion,
                $idCurso,
                $idPeriodo,
                $idDocente,
                $codigo,
                $vacantes,
                $disponibles
            );

            $this->seccionRepositorio->actualizar($actualizada);

            return [
                'success' => true,
                'message' => 'Sección actualizada correctamente.',
                'data'    => $this->mapSeccion($actualizada)
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
                'message' => 'Error al actualizar sección: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    private function mapSeccion(Seccion $s): array
    {
        return [
            'id_seccion'           => $s->getIdSeccion(),
            'id_curso'             => $s->getIdCurso(),
            'id_periodo'           => $s->getIdPeriodo(),
            'id_docente'           => $s->getIdDocente(),
            'codigo'               => $s->getCodigo(),
            'vacantes'             => $s->getVacantes(),
            'vacantes_disponibles' => $s->getVacantesDisponibles()
        ];
    }
}

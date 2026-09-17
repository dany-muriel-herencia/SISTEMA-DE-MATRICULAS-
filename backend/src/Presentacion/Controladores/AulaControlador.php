<?php

declare(strict_types=1);

namespace App\Presentacion\Controladores;

use App\Dominio\Entidades\Aula;
use App\Dominio\Repositorios\AulaRepositorio;
use DomainException;
use InvalidArgumentException;
use Throwable;

final class AulaControlador
{
    public function __construct(
        private readonly AulaRepositorio $aulaRepositorio
    ) {}

    public function registrar(array $datos): array
    {
        try {
            $nombre     = trim((string)($datos['nombre'] ?? ''));
            $ubicacion  = trim((string)($datos['ubicacion'] ?? ''));
            $capacidad  = (int)($datos['capacidad'] ?? 30);
            $tipo       = trim((string)($datos['tipo'] ?? 'TEORIA'));
            $disponible = isset($datos['disponible']) ? (bool)$datos['disponible'] : true;
            $estado     = isset($datos['estado']) ? (bool)$datos['estado'] : true;

            $aula = new Aula(
                1,
                $nombre,
                $ubicacion,
                $capacidad,
                $tipo,
                $disponible,
                $estado
            );

            $this->aulaRepositorio->guardar($aula);

            return [
                'success' => true,
                'message' => 'Aula registrada correctamente.',
                'data'    => $this->mapAula($aula)
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
                'message' => 'Error al registrar aula: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function buscarPorId(int $idAula): array
    {
        try {
            $aula = $this->aulaRepositorio->buscarPorId($idAula);
            if ($aula === null) {
                return [
                    'success' => false,
                    'message' => "Aula con ID {$idAula} no encontrada.",
                    'data'    => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Aula encontrada.',
                'data'    => $this->mapAula($aula)
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al buscar aula: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function listar(): array
    {
        try {
            $aulas = $this->aulaRepositorio->listar();
            $data = array_map(fn(Aula $a) => $this->mapAula($a), $aulas);

            return [
                'success' => true,
                'message' => 'Aulas obtenidas correctamente.',
                'data'    => $data
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al listar aulas: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function listarDisponibles(): array
    {
        try {
            $aulas = $this->aulaRepositorio->listarDisponibles();
            $data = array_map(fn(Aula $a) => $this->mapAula($a), $aulas);

            return [
                'success' => true,
                'message' => 'Aulas disponibles obtenidas correctamente.',
                'data'    => $data
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al listar aulas disponibles: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function actualizar(array $datos): array
    {
        try {
            $idAula    = (int)($datos['id_aula'] ?? $datos['id'] ?? 0);
            $existente = $this->aulaRepositorio->buscarPorId($idAula);
            if ($existente === null) {
                return [
                    'success' => false,
                    'message' => "Aula con ID {$idAula} no encontrada.",
                    'data'    => null
                ];
            }

            $nombre     = trim((string)($datos['nombre'] ?? $existente->getNombre()));
            $ubicacion  = trim((string)($datos['ubicacion'] ?? $existente->getUbicacion()));
            $capacidad  = isset($datos['capacidad']) ? (int)$datos['capacidad'] : $existente->getCapacidad();
            $tipo       = trim((string)($datos['tipo'] ?? $existente->getTipo()));
            $disponible = isset($datos['disponible']) ? (bool)$datos['disponible'] : $existente->getDisponible();
            $estado     = isset($datos['estado']) ? (bool)$datos['estado'] : $existente->getEstado();

            $actualizada = new Aula(
                $idAula,
                $nombre,
                $ubicacion,
                $capacidad,
                $tipo,
                $disponible,
                $estado
            );

            $this->aulaRepositorio->actualizar($actualizada);

            return [
                'success' => true,
                'message' => 'Aula actualizada correctamente.',
                'data'    => $this->mapAula($actualizada)
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
                'message' => 'Error al actualizar aula: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    private function mapAula(Aula $a): array
    {
        return [
            'id_aula'    => $a->getIdAula(),
            'nombre'     => $a->getNombre(),
            'ubicacion'  => $a->getUbicacion(),
            'capacidad'  => $a->getCapacidad(),
            'tipo'       => $a->getTipo(),
            'disponible' => $a->getDisponible(),
            'estado'     => $a->getEstado()
        ];
    }
}

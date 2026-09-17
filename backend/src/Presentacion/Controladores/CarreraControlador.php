<?php

declare(strict_types=1);

namespace App\Presentacion\Controladores;

use App\Dominio\Entidades\Carrera;
use App\Dominio\Repositorios\CarreraRepositorio;
use DomainException;
use InvalidArgumentException;
use Throwable;

final class CarreraControlador
{
    public function __construct(
        private readonly CarreraRepositorio $carreraRepositorio
    ) {}

    public function registrar(array $datos): array
    {
        try {
            $idEscuela = (int)($datos['id_escuela'] ?? 0);
            $nombre    = trim((string)($datos['nombre'] ?? ''));
            $codigo    = trim((string)($datos['codigo'] ?? ''));
            $duracion  = (int)($datos['duracion'] ?? 10);
            $estado    = isset($datos['estado']) ? (bool)$datos['estado'] : true;

            $carrera = new Carrera(
                0,
                $idEscuela,
                $nombre,
                $codigo,
                $duracion,
                $estado
            );

            $this->carreraRepositorio->guardar($carrera);

            return [
                'success' => true,
                'message' => 'Carrera registrada correctamente.',
                'data'    => $this->mapCarrera($carrera)
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
                'message' => 'Error al registrar carrera: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function buscarPorId(int $idCarrera): array
    {
        try {
            $carrera = $this->carreraRepositorio->buscarPorId($idCarrera);
            if ($carrera === null) {
                return [
                    'success' => false,
                    'message' => "Carrera con ID {$idCarrera} no encontrada.",
                    'data'    => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Carrera encontrada.',
                'data'    => $this->mapCarrera($carrera)
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al buscar carrera: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function buscarPorCodigo(string $codigo): array
    {
        try {
            $carrera = $this->carreraRepositorio->buscarPorCodigo($codigo);
            if ($carrera === null) {
                return [
                    'success' => false,
                    'message' => "Carrera con código {$codigo} no encontrada.",
                    'data'    => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Carrera encontrada.',
                'data'    => $this->mapCarrera($carrera)
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al buscar carrera por código: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function listar(): array
    {
        try {
            $carreras = $this->carreraRepositorio->listar();
            $data = array_map(fn(Carrera $c) => $this->mapCarrera($c), $carreras);

            return [
                'success' => true,
                'message' => 'Carreras obtenidas correctamente.',
                'data'    => $data
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al listar carreras: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function actualizar(array $datos): array
    {
        try {
            $idCarrera = (int)($datos['id_carrera'] ?? $datos['id'] ?? 0);
            $existente = $this->carreraRepositorio->buscarPorId($idCarrera);
            if ($existente === null) {
                return [
                    'success' => false,
                    'message' => "Carrera con ID {$idCarrera} no encontrada.",
                    'data'    => null
                ];
            }

            $idEscuela = (int)($datos['id_escuela'] ?? $existente->getIdEscuela());
            $nombre    = trim((string)($datos['nombre'] ?? $existente->getNombre()));
            $codigo    = trim((string)($datos['codigo'] ?? $existente->getCodigo()));
            $duracion  = (int)($datos['duracion'] ?? $existente->getDuracion());
            $estado    = isset($datos['estado']) ? (bool)$datos['estado'] : $existente->getEstado();

            $actualizada = new Carrera(
                $idCarrera,
                $idEscuela,
                $nombre,
                $codigo,
                $duracion,
                $estado
            );

            $this->carreraRepositorio->actualizar($actualizada);

            return [
                'success' => true,
                'message' => 'Carrera actualizada correctamente.',
                'data'    => $this->mapCarrera($actualizada)
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
                'message' => 'Error al actualizar carrera: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    private function mapCarrera(Carrera $c): array
    {
        return [
            'id_carrera' => $c->getIdCarrera(),
            'id_escuela' => $c->getIdEscuela(),
            'nombre'     => $c->getNombre(),
            'codigo'     => $c->getCodigo(),
            'duracion'   => $c->getDuracion(),
            'estado'     => $c->getEstado()
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Presentacion\Controladores;

use App\Dominio\Entidades\Facultad;
use App\Dominio\Repositorios\FacultadRepositorio;
use DomainException;
use InvalidArgumentException;
use Throwable;

final class FacultadControlador
{
    public function __construct(
        private readonly FacultadRepositorio $facultadRepositorio
    ) {}

    public function registrar(array $datos): array
    {
        try {
            $nombre      = trim((string)($datos['nombre'] ?? ''));
            $descripcion = trim((string)($datos['descripcion'] ?? ''));
            $decano      = trim((string)($datos['decano'] ?? ''));

            $facultad = new Facultad(
                0,
                $nombre,
                $descripcion,
                $decano
            );

            $this->facultadRepositorio->guardar($facultad);

            return [
                'success' => true,
                'message' => 'Facultad registrada correctamente.',
                'data'    => $this->mapFacultad($facultad)
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
                'message' => 'Error al registrar facultad: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function buscarPorId(int $idFacultad): array
    {
        try {
            $facultad = $this->facultadRepositorio->buscarPorId($idFacultad);
            if ($facultad === null) {
                return [
                    'success' => false,
                    'message' => "Facultad con ID {$idFacultad} no encontrada.",
                    'data'    => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Facultad encontrada.',
                'data'    => $this->mapFacultad($facultad)
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al buscar facultad: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function listar(): array
    {
        try {
            $facultades = $this->facultadRepositorio->listar();
            $data = array_map(fn(Facultad $f) => $this->mapFacultad($f), $facultades);

            return [
                'success' => true,
                'message' => 'Facultades obtenidas correctamente.',
                'data'    => $data
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al listar facultades: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function actualizar(array $datos): array
    {
        try {
            $idFacultad = (int)($datos['id_facultad'] ?? $datos['id'] ?? 0);
            $existente  = $this->facultadRepositorio->buscarPorId($idFacultad);
            if ($existente === null) {
                return [
                    'success' => false,
                    'message' => "Facultad con ID {$idFacultad} no encontrada.",
                    'data'    => null
                ];
            }

            $nombre      = trim((string)($datos['nombre'] ?? $existente->getNombre()));
            $descripcion = trim((string)($datos['descripcion'] ?? $existente->getDescripcion()));
            $decano      = trim((string)($datos['decano'] ?? $existente->getDecano()));

            $actualizada = new Facultad(
                $idFacultad,
                $nombre,
                $descripcion,
                $decano
            );

            $this->facultadRepositorio->actualizar($actualizada);

            return [
                'success' => true,
                'message' => 'Facultad actualizada correctamente.',
                'data'    => $this->mapFacultad($actualizada)
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
                'message' => 'Error al actualizar facultad: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    private function mapFacultad(Facultad $f): array
    {
        return [
            'id_facultad' => $f->getIdFacultad(),
            'nombre'      => $f->getNombre(),
            'descripcion' => $f->getDescripcion(),
            'decano'      => $f->getDecano()
        ];
    }
}

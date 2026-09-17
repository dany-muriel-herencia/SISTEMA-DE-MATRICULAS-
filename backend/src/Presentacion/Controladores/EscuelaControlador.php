<?php

declare(strict_types=1);

namespace App\Presentacion\Controladores;

use App\Dominio\Entidades\Escuela;
use App\Dominio\Repositorios\EscuelaRepositorio;
use DomainException;
use InvalidArgumentException;
use Throwable;

final class EscuelaControlador
{
    public function __construct(
        private readonly EscuelaRepositorio $escuelaRepositorio
    ) {}

    public function registrar(array $datos): array
    {
        try {
            $idFacultad  = (int)($datos['id_facultad'] ?? 0);
            $nombre      = trim((string)($datos['nombre'] ?? ''));
            $descripcion = trim((string)($datos['descripcion'] ?? ''));
            $director    = trim((string)($datos['director'] ?? ''));

            $escuela = new Escuela(
                0,
                $idFacultad,
                $nombre,
                $descripcion,
                $director
            );

            $this->escuelaRepositorio->guardar($escuela);

            return [
                'success' => true,
                'message' => 'Escuela registrada correctamente.',
                'data'    => $this->mapEscuela($escuela)
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
                'message' => 'Error al registrar escuela: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function buscarPorId(int $idEscuela): array
    {
        try {
            $escuela = $this->escuelaRepositorio->buscarPorId($idEscuela);
            if ($escuela === null) {
                return [
                    'success' => false,
                    'message' => "Escuela con ID {$idEscuela} no encontrada.",
                    'data'    => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Escuela encontrada.',
                'data'    => $this->mapEscuela($escuela)
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al buscar escuela: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function listar(): array
    {
        try {
            $escuelas = $this->escuelaRepositorio->listar();
            $data = array_map(fn(Escuela $e) => $this->mapEscuela($e), $escuelas);

            return [
                'success' => true,
                'message' => 'Escuelas obtenidas correctamente.',
                'data'    => $data
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al listar escuelas: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function actualizar(array $datos): array
    {
        try {
            $idEscuela   = (int)($datos['id_escuela'] ?? $datos['id'] ?? 0);
            $existente   = $this->escuelaRepositorio->buscarPorId($idEscuela);
            if ($existente === null) {
                return [
                    'success' => false,
                    'message' => "Escuela con ID {$idEscuela} no encontrada.",
                    'data'    => null
                ];
            }

            $idFacultad  = (int)($datos['id_facultad'] ?? $existente->getIdFacultad());
            $nombre      = trim((string)($datos['nombre'] ?? $existente->getNombre()));
            $descripcion = trim((string)($datos['descripcion'] ?? $existente->getDescripcion()));
            $director    = trim((string)($datos['director'] ?? $existente->getDirector()));

            $actualizada = new Escuela(
                $idEscuela,
                $idFacultad,
                $nombre,
                $descripcion,
                $director
            );

            $this->escuelaRepositorio->actualizar($actualizada);

            return [
                'success' => true,
                'message' => 'Escuela actualizada correctamente.',
                'data'    => $this->mapEscuela($actualizada)
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
                'message' => 'Error al actualizar escuela: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    private function mapEscuela(Escuela $e): array
    {
        return [
            'id_escuela'  => $e->getIdEscuela(),
            'id_facultad' => $e->getIdFacultad(),
            'nombre'      => $e->getNombre(),
            'descripcion' => $e->getDescripcion(),
            'director'    => $e->getDirector()
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Presentacion\Controladores;

use App\Dominio\Entidades\Prerequisito;
use App\Dominio\Repositorios\PrerequisitoRepositorio;
use DomainException;
use InvalidArgumentException;
use Throwable;

final class PrerequisitoControlador
{
    public function __construct(
        private readonly PrerequisitoRepositorio $prerequisitoRepositorio
    ) {}

    public function registrar(array $datos): array
    {
        try {
            $idCurso          = (int)($datos['id_curso'] ?? 0);
            $idCursoRequerido = (int)($datos['id_curso_requerido'] ?? 0);

            $prerequisito = new Prerequisito(
                1,
                $idCurso,
                $idCursoRequerido
            );

            $this->prerequisitoRepositorio->guardar($prerequisito);

            return [
                'success' => true,
                'message' => 'Prerrequisito registrado correctamente.',
                'data'    => $this->mapPrerequisito($prerequisito)
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
                'message' => 'Error al registrar prerrequisito: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function buscarPorId(int $idPrerequisito): array
    {
        try {
            $prerequisito = $this->prerequisitoRepositorio->buscarPorId($idPrerequisito);
            if ($prerequisito === null) {
                return [
                    'success' => false,
                    'message' => "Prerrequisito con ID {$idPrerequisito} no encontrado.",
                    'data'    => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Prerrequisito encontrado.',
                'data'    => $this->mapPrerequisito($prerequisito)
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al buscar prerrequisito: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function listar(): array
    {
        try {
            $prerequisitos = $this->prerequisitoRepositorio->listar();
            $data = array_map(fn(Prerequisito $p) => $this->mapPrerequisito($p), $prerequisitos);

            return [
                'success' => true,
                'message' => 'Prerrequisitos obtenidos correctamente.',
                'data'    => $data
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al listar prerrequisitos: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function eliminar(int $idPrerequisito): array
    {
        try {
            $this->prerequisitoRepositorio->eliminar($idPrerequisito);

            return [
                'success' => true,
                'message' => "Prerrequisito con ID {$idPrerequisito} eliminado correctamente.",
                'data'    => ['id_prerequisito' => $idPrerequisito]
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al eliminar prerrequisito: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    private function mapPrerequisito(Prerequisito $p): array
    {
        return [
            'id_prerequisito'    => $p->getIdPrerequisito(),
            'id_curso'           => $p->getIdCurso(),
            'id_curso_requerido' => $p->getIdCursoRequerido()
        ];
    }
}

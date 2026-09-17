<?php

declare(strict_types=1);

namespace App\Presentacion\Controladores;

use App\Dominio\Entidades\Curso;
use App\Dominio\Repositorios\CursoRepositorio;
use DomainException;
use InvalidArgumentException;
use Throwable;

final class CursoControlador
{
    public function __construct(
        private readonly CursoRepositorio $cursoRepositorio
    ) {}

    public function registrar(array $datos): array
    {
        try {
            $nombre        = trim((string)($datos['nombre'] ?? ''));
            $codigo        = trim((string)($datos['codigo'] ?? ''));
            $creditos      = (int)($datos['creditos'] ?? 0);
            $horasTeoria   = (int)($datos['horas_teoria'] ?? 0);
            $horasPractica = (int)($datos['horas_practica'] ?? 0);
            $ciclo         = trim((string)($datos['ciclo'] ?? 'I'));
            $estado        = isset($datos['estado']) ? (bool)$datos['estado'] : true;

            $curso = new Curso(
                1,
                $nombre,
                $codigo,
                $creditos,
                $horasTeoria,
                $horasPractica,
                $ciclo,
                $estado
            );

            $this->cursoRepositorio->guardar($curso);

            return [
                'success' => true,
                'message' => 'Curso registrado correctamente.',
                'data'    => $this->mapCurso($curso)
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
                'message' => 'Error al registrar curso: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function buscarPorId(int $idCurso): array
    {
        try {
            $curso = $this->cursoRepositorio->buscarPorId($idCurso);
            if ($curso === null) {
                return [
                    'success' => false,
                    'message' => "Curso con ID {$idCurso} no encontrado.",
                    'data'    => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Curso encontrado.',
                'data'    => $this->mapCurso($curso)
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al buscar curso: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function buscarPorCodigo(string $codigo): array
    {
        try {
            $curso = $this->cursoRepositorio->buscarPorCodigo($codigo);
            if ($curso === null) {
                return [
                    'success' => false,
                    'message' => "Curso con código {$codigo} no encontrado.",
                    'data'    => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Curso encontrado.',
                'data'    => $this->mapCurso($curso)
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al buscar curso por código: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function listar(): array
    {
        try {
            $cursos = $this->cursoRepositorio->listarActivos();
            $data = array_map(fn(Curso $c) => $this->mapCurso($c), $cursos);

            return [
                'success' => true,
                'message' => 'Cursos obtenidos correctamente.',
                'data'    => $data
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al listar cursos: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function actualizar(array $datos): array
    {
        try {
            $idCurso = (int)($datos['id_curso'] ?? $datos['id'] ?? 0);
            $existente = $this->cursoRepositorio->buscarPorId($idCurso);
            if ($existente === null) {
                return [
                    'success' => false,
                    'message' => "Curso con ID {$idCurso} no encontrado.",
                    'data'    => null
                ];
            }

            $nombre        = trim((string)($datos['nombre'] ?? $existente->getNombre()));
            $codigo        = trim((string)($datos['codigo'] ?? $existente->getCodigo()));
            $creditos      = isset($datos['creditos']) ? (int)$datos['creditos'] : $existente->getCreditos();
            $horasTeoria   = isset($datos['horas_teoria']) ? (int)$datos['horas_teoria'] : $existente->getHorasTeoria();
            $horasPractica = isset($datos['horas_practica']) ? (int)$datos['horas_practica'] : $existente->getHorasPractica();
            $ciclo         = trim((string)($datos['ciclo'] ?? $existente->getCiclo()));
            $estado        = isset($datos['estado']) ? (bool)$datos['estado'] : $existente->getEstado();

            $actualizado = new Curso(
                $idCurso,
                $nombre,
                $codigo,
                $creditos,
                $horasTeoria,
                $horasPractica,
                $ciclo,
                $estado
            );

            $this->cursoRepositorio->actualizar($actualizado);

            return [
                'success' => true,
                'message' => 'Curso actualizado correctamente.',
                'data'    => $this->mapCurso($actualizado)
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
                'message' => 'Error al actualizar curso: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    private function mapCurso(Curso $c): array
    {
        return [
            'id_curso'       => $c->getIdCurso(),
            'nombre'         => $c->getNombre(),
            'codigo'         => $c->getCodigo(),
            'creditos'       => $c->getCreditos(),
            'horas_teoria'   => $c->getHorasTeoria(),
            'horas_practica' => $c->getHorasPractica(),
            'ciclo'          => $c->getCiclo(),
            'estado'         => $c->getEstado()
        ];
    }
}

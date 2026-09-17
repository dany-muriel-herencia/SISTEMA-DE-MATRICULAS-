<?php

declare(strict_types=1);

namespace App\Presentacion\Controladores;

use App\Dominio\Entidades\Estudiante;
use App\Dominio\Repositorios\EstudianteRepositorio;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use Throwable;

final class EstudianteControlador
{
    public function __construct(
        private readonly EstudianteRepositorio $estudianteRepositorio
    ) {}

    public function registrar(array $datos): array
    {
        try {
            $nombre       = trim((string)($datos['nombre'] ?? ''));
            $email        = trim((string)($datos['email'] ?? ''));
            $contrasenha  = (string)($datos['contrasenha'] ?? $datos['password'] ?? '');
            $rol          = 'ESTUDIANTE';
            $estado       = isset($datos['estado']) ? (bool)$datos['estado'] : true;
            $codigoUniv   = trim((string)($datos['codigo_universitario'] ?? $datos['codigo'] ?? ''));
            $dni          = trim((string)($datos['dni'] ?? ''));
            $fechaNac     = new DateTimeImmutable((string)($datos['fecha_nacimiento'] ?? '2000-01-01'));
            $fechaIng     = new DateTimeImmutable((string)($datos['fecha_ingreso'] ?? 'now'));
            $promedio     = (float)($datos['promedio_academico'] ?? $datos['promedio'] ?? 0.0);

            $estudiante = new Estudiante(
                0,
                $nombre,
                $email,
                $contrasenha,
                $rol,
                $estado,
                new DateTimeImmutable(),
                $codigoUniv,
                $dni,
                $fechaNac,
                $fechaIng,
                $promedio
            );

            $this->estudianteRepositorio->guardar($estudiante);

            return [
                'success' => true,
                'message' => 'Estudiante registrado correctamente.',
                'data'    => $this->mapEstudiante($estudiante)
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
                'message' => 'Error al registrar estudiante: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function buscarPorId(int $idUsuario): array
    {
        try {
            $estudiante = $this->estudianteRepositorio->buscarPorId($idUsuario);
            if ($estudiante === null) {
                return [
                    'success' => false,
                    'message' => "Estudiante con ID {$idUsuario} no encontrado.",
                    'data'    => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Estudiante encontrado.',
                'data'    => $this->mapEstudiante($estudiante)
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al buscar estudiante: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function buscarPorCodigo(string $codigo): array
    {
        try {
            $estudiante = $this->estudianteRepositorio->buscarPorCodigo($codigo);
            if ($estudiante === null) {
                return [
                    'success' => false,
                    'message' => "Estudiante con código {$codigo} no encontrado.",
                    'data'    => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Estudiante encontrado.',
                'data'    => $this->mapEstudiante($estudiante)
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al buscar estudiante por código: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function buscarPorDni(string $dni): array
    {
        try {
            $estudiante = $this->estudianteRepositorio->buscarPorDni($dni);
            if ($estudiante === null) {
                return [
                    'success' => false,
                    'message' => "Estudiante con DNI {$dni} no encontrado.",
                    'data'    => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Estudiante encontrado.',
                'data'    => $this->mapEstudiante($estudiante)
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al buscar estudiante por DNI: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function actualizar(array $datos): array
    {
        try {
            $idUsuario = (int)($datos['id_usuario'] ?? $datos['id'] ?? 0);
            $estudianteExistente = $this->estudianteRepositorio->buscarPorId($idUsuario);
            if ($estudianteExistente === null) {
                return [
                    'success' => false,
                    'message' => "Estudiante con ID {$idUsuario} no encontrado.",
                    'data'    => null
                ];
            }

            $codigoUniv = trim((string)($datos['codigo_universitario'] ?? $datos['codigo'] ?? $estudianteExistente->getCodigoUniversitario()));
            $dni        = trim((string)($datos['dni'] ?? $estudianteExistente->getDni()));
            $fechaNac   = isset($datos['fecha_nacimiento']) ? new DateTimeImmutable((string)$datos['fecha_nacimiento']) : $estudianteExistente->getFechaNacimiento();
            $fechaIng   = isset($datos['fecha_ingreso']) ? new DateTimeImmutable((string)$datos['fecha_ingreso']) : $estudianteExistente->getFechaIngreso();
            $promedio   = isset($datos['promedio_academico']) ? (float)$datos['promedio_academico'] : $estudianteExistente->getPromedioAcademico();

            $actualizado = new Estudiante(
                $idUsuario,
                $estudianteExistente->getNombre(),
                $estudianteExistente->getEmail(),
                $estudianteExistente->getContrasenha(),
                $estudianteExistente->getRol(),
                $estudianteExistente->isEstado(),
                $estudianteExistente->getFechaCreacion(),
                $codigoUniv,
                $dni,
                $fechaNac,
                $fechaIng,
                $promedio
            );

            $this->estudianteRepositorio->actualizar($actualizado);

            return [
                'success' => true,
                'message' => 'Estudiante actualizado correctamente.',
                'data'    => $this->mapEstudiante($actualizado)
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
                'message' => 'Error al actualizar estudiante: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    private function mapEstudiante(Estudiante $e): array
    {
        return [
            'id_usuario'           => $e->getIdUsuario(),
            'nombre'               => $e->getNombre(),
            'email'                => $e->getEmail(),
            'rol'                  => $e->getRol(),
            'codigo_universitario' => $e->getCodigoUniversitario(),
            'dni'                  => $e->getDni(),
            'fecha_nacimiento'     => $e->getFechaNacimiento()->format('Y-m-d'),
            'fecha_ingreso'        => $e->getFechaIngreso()->format('Y-m-d'),
            'promedio_academico'   => $e->getPromedioAcademico()
        ];
    }
}

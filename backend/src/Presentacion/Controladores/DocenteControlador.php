<?php

declare(strict_types=1);

namespace App\Presentacion\Controladores;

use App\Dominio\Entidades\Docente;
use App\Dominio\Repositorios\DocenteRepositorio;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use Throwable;

final class DocenteControlador
{
    public function __construct(
        private readonly DocenteRepositorio $docenteRepositorio
    ) {}

    public function registrar(array $datos): array
    {
        try {
            $idUsuario      = (int)($datos['id_usuario'] ?? $datos['id'] ?? 0);
            $nombre         = trim((string)($datos['nombre'] ?? ''));
            $email          = trim((string)($datos['email'] ?? ''));
            $contrasenha    = (string)($datos['contrasenha'] ?? $datos['password'] ?? '');
            $rol            = 'DOCENTE';
            $estado         = isset($datos['estado']) ? (bool)$datos['estado'] : true;
            $codigo         = trim((string)($datos['codigo'] ?? ''));
            $especialidad   = trim((string)($datos['especialidad'] ?? ''));
            $gradoAcademico = trim((string)($datos['grado_academico'] ?? ''));

            $docente = new Docente(
                $idUsuario,
                $nombre,
                $email,
                $contrasenha,
                $rol,
                $estado,
                new DateTimeImmutable(),
                $codigo,
                $especialidad,
                $gradoAcademico
            );

            $this->docenteRepositorio->guardar($docente);

            return [
                'success' => true,
                'message' => 'Docente registrado correctamente.',
                'data'    => $this->mapDocente($docente)
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
                'message' => 'Error al registrar docente: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function buscarPorId(int $idUsuario): array
    {
        try {
            $docente = $this->docenteRepositorio->buscarPorId($idUsuario);
            if ($docente === null) {
                return [
                    'success' => false,
                    'message' => "Docente con ID {$idUsuario} no encontrado.",
                    'data'    => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Docente encontrado.',
                'data'    => $this->mapDocente($docente)
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al buscar docente: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function buscarPorCodigo(string $codigo): array
    {
        try {
            $docente = $this->docenteRepositorio->buscarPorCodigo($codigo);
            if ($docente === null) {
                return [
                    'success' => false,
                    'message' => "Docente con código {$codigo} no encontrado.",
                    'data'    => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Docente encontrado.',
                'data'    => $this->mapDocente($docente)
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al buscar docente por código: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function actualizar(array $datos): array
    {
        try {
            $idUsuario = (int)($datos['id_usuario'] ?? $datos['id'] ?? 0);
            $docenteExistente = $this->docenteRepositorio->buscarPorId($idUsuario);
            if ($docenteExistente === null) {
                return [
                    'success' => false,
                    'message' => "Docente con ID {$idUsuario} no encontrado.",
                    'data'    => null
                ];
            }

            $codigo         = trim((string)($datos['codigo'] ?? $docenteExistente->getCodigo()));
            $especialidad   = trim((string)($datos['especialidad'] ?? $docenteExistente->getEspecialidad()));
            $gradoAcademico = trim((string)($datos['grado_academico'] ?? $docenteExistente->getGradoAcademico()));

            $actualizado = new Docente(
                $idUsuario,
                $docenteExistente->getNombre(),
                $docenteExistente->getEmail(),
                $docenteExistente->getContrasenha(),
                $docenteExistente->getRol(),
                $docenteExistente->isEstado(),
                $docenteExistente->getFechaCreacion(),
                $codigo,
                $especialidad,
                $gradoAcademico
            );

            $this->docenteRepositorio->actualizar($actualizado);

            return [
                'success' => true,
                'message' => 'Docente actualizado correctamente.',
                'data'    => $this->mapDocente($actualizado)
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
                'message' => 'Error al actualizar docente: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    private function mapDocente(Docente $d): array
    {
        return [
            'id_usuario'      => $d->getIdUsuario(),
            'codigo'          => $d->getCodigo(),
            'especialidad'    => $d->getEspecialidad(),
            'grado_academico' => $d->getGradoAcademico()
        ];
    }
}

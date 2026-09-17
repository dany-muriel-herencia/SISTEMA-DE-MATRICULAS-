<?php

declare(strict_types=1);

namespace App\Presentacion\Controladores;

use App\Dominio\Entidades\Horario;
use App\Dominio\Repositorios\HorarioRepositorio;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use Throwable;

final class HorarioControlador
{
    public function __construct(
        private readonly HorarioRepositorio $horarioRepositorio
    ) {}

    public function registrar(array $datos): array
    {
        try {
            $idSeccion  = (int)($datos['id_seccion'] ?? 0);
            $idAula     = (int)($datos['id_aula'] ?? 0);
            $diaSemana  = strtoupper(trim((string)($datos['dia_semana'] ?? '')));
            $horaInicio = trim((string)($datos['hora_inicio'] ?? ''));
            $horaFin    = trim((string)($datos['hora_fin'] ?? ''));
            $modalidad  = strtoupper(trim((string)($datos['modalidad'] ?? 'PRESENCIAL')));

            if ($idAula > 0) {
                $conflicto = $this->horarioRepositorio->existeConflictoAula($idAula, $diaSemana, $horaInicio, $horaFin);
                if ($conflicto) {
                    throw new DomainException("Conflicto de horario: El aula {$idAula} ya se encuentra ocupada.");
                }
            }

            $inicioObj = new DateTimeImmutable("2000-01-01 {$horaInicio}");
            $finObj    = new DateTimeImmutable("2000-01-01 {$horaFin}");

            $horario = new Horario(
                1,
                $idSeccion,
                $idAula,
                $diaSemana,
                $inicioObj,
                $finObj,
                $modalidad
            );

            $this->horarioRepositorio->guardar($horario);

            return [
                'success' => true,
                'message' => 'Horario registrado correctamente.',
                'data'    => $this->mapHorario($horario)
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
                'message' => 'Error al registrar horario: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function buscarPorId(int $idHorario): array
    {
        try {
            $horario = $this->horarioRepositorio->buscarPorId($idHorario);
            if ($horario === null) {
                return [
                    'success' => false,
                    'message' => "Horario con ID {$idHorario} no encontrado.",
                    'data'    => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Horario encontrado.',
                'data'    => $this->mapHorario($horario)
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al buscar horario: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function listar(): array
    {
        try {
            $horarios = $this->horarioRepositorio->listar();
            $data = array_map(fn(Horario $h) => $this->mapHorario($h), $horarios);

            return [
                'success' => true,
                'message' => 'Horarios obtenidos correctamente.',
                'data'    => $data
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al listar horarios: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function actualizar(array $datos): array
    {
        try {
            $idHorario = (int)($datos['id_horario'] ?? $datos['id'] ?? 0);
            $existente = $this->horarioRepositorio->buscarPorId($idHorario);
            if ($existente === null) {
                return [
                    'success' => false,
                    'message' => "Horario con ID {$idHorario} no encontrado.",
                    'data'    => null
                ];
            }

            $idSeccion  = (int)($datos['id_seccion'] ?? $existente->getIdSeccion());
            $idAula     = (int)($datos['id_aula'] ?? $existente->getIdAula());
            $diaSemana  = strtoupper(trim((string)($datos['dia_semana'] ?? $existente->getDiaSemana())));
            $horaInicio = trim((string)($datos['hora_inicio'] ?? $existente->getHoraInicio()->format('H:i:s')));
            $horaFin    = trim((string)($datos['hora_fin'] ?? $existente->getHoraFin()->format('H:i:s')));
            $modalidad  = strtoupper(trim((string)($datos['modalidad'] ?? $existente->getModalidad())));

            $inicioObj = new DateTimeImmutable("2000-01-01 {$horaInicio}");
            $finObj    = new DateTimeImmutable("2000-01-01 {$horaFin}");

            $actualizado = new Horario(
                $idHorario,
                $idSeccion,
                $idAula,
                $diaSemana,
                $inicioObj,
                $finObj,
                $modalidad
            );

            $this->horarioRepositorio->actualizar($actualizado);

            return [
                'success' => true,
                'message' => 'Horario actualizado correctamente.',
                'data'    => $this->mapHorario($actualizado)
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
                'message' => 'Error al actualizar horario: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function eliminar(int $idHorario): array
    {
        try {
            $this->horarioRepositorio->eliminar($idHorario);

            return [
                'success' => true,
                'message' => "Horario con ID {$idHorario} eliminado correctamente.",
                'data'    => ['id_horario' => $idHorario]
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al eliminar horario: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    private function mapHorario(Horario $h): array
    {
        return [
            'id_horario'  => $h->getIdHorario(),
            'id_seccion'  => $h->getIdSeccion(),
            'id_aula'     => $h->getIdAula(),
            'dia_semana'  => $h->getDiaSemana(),
            'hora_inicio' => $h->getHoraInicio()->format('H:i'),
            'hora_fin'    => $h->getHoraFin()->format('H:i'),
            'modalidad'   => $h->getModalidad()
        ];
    }
}

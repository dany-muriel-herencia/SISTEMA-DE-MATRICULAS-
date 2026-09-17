<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Horario;

use App\Dominio\Entidades\Horario;
use App\Dominio\Repositorios\AulaRepositorio;
use App\Dominio\Repositorios\HorarioRepositorio;
use App\Dominio\Repositorios\SeccionRepositorio;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;

/**
 * CU-33: Crear horarios para las secciones.
 * CU-34: Evitar el uso simultáneo de una misma aula.
 * Actor Secundario / RN-08 (CU-35): Evitar cruces de horario para docentes.
 * CU-36: Registrar clases presenciales, virtuales o híbridas.
 */
class CrearHorarioSeccion
{
    private HorarioRepositorio $horarioRepo;
    private SeccionRepositorio $seccionRepo;
    private AulaRepositorio $aulaRepo;

    public function __construct(
        HorarioRepositorio $horarioRepo,
        SeccionRepositorio $seccionRepo,
        AulaRepositorio $aulaRepo
    ) {
        $this->horarioRepo = $horarioRepo;
        $this->seccionRepo = $seccionRepo;
        $this->aulaRepo    = $aulaRepo;
    }

    public function ejecutar(array $datos): array
    {
        $idSeccion  = (int) ($datos['id_seccion'] ?? 0);
        $idAula     = (int) ($datos['id_aula'] ?? 0);
        $diaSemana  = strtoupper(trim($datos['dia_semana'] ?? ''));
        $horaInicio = trim($datos['hora_inicio'] ?? '');
        $horaFin    = trim($datos['hora_fin'] ?? '');
        $modalidad  = strtoupper(trim($datos['modalidad'] ?? 'PRESENCIAL'));

        if ($idSeccion <= 0) {
            throw new InvalidArgumentException('El ID de la sección es obligatorio.');
        }

        $diasValidos = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO', 'DOMINGO'];
        if (!in_array($diaSemana, $diasValidos, true)) {
            throw new InvalidArgumentException('Día de la semana inválido. Debe ser: LUNES a DOMINGO.');
        }

        // CU-36: Validar modalidades válidas
        $modalidadesValidas = ['PRESENCIAL', 'VIRTUAL', 'HIBRIDO'];
        if (!in_array($modalidad, $modalidadesValidas, true)) {
            throw new InvalidArgumentException('Modalidad inválida. Las modalidades permitidas son: PRESENCIAL, VIRTUAL, HIBRIDO.');
        }

        if (empty($horaInicio) || empty($horaFin)) {
            throw new InvalidArgumentException('Las horas de inicio y fin son obligatorias.');
        }

        $inicioObj = new DateTimeImmutable("2000-01-01 {$horaInicio}");
        $finObj    = new DateTimeImmutable("2000-01-01 {$horaFin}");

        if ($finObj <= $inicioObj) {
            throw new InvalidArgumentException('La hora de fin debe ser posterior a la hora de inicio.');
        }

        // Verificar existencia de la Sección
        $seccion = $this->seccionRepo->buscarPorId($idSeccion);
        if ($seccion === null) {
            throw new DomainException("Sección con ID {$idSeccion} no encontrada.");
        }

        // CU-35 / RN-08: Evitar cruces de horario para el docente asignado a la sección
        $idDocente = $seccion->getIdDocente();
        if ($idDocente > 0) {
            $conflictoDocente = $this->horarioRepo->existeConflictoDocente(
                $idDocente,
                $diaSemana,
                $horaInicio,
                $horaFin
            );
            if ($conflictoDocente) {
                throw new DomainException("Conflicto de horario: El docente asignado ya tiene una clase programada el {$diaSemana} de {$horaInicio} a {$horaFin} (RN-08).");
            }
        }

        // CU-34: Evitar el uso simultáneo de una misma aula (para clases presenciales o híbridas)
        if ($modalidad !== 'VIRTUAL' || $idAula > 0) {
            if ($idAula <= 0) {
                throw new InvalidArgumentException('Para clases presenciales o híbridas se requiere asignar un aula física.');
            }

            $aula = $this->aulaRepo->buscarPorId($idAula);
            if ($aula === null) {
                throw new DomainException("Aula con ID {$idAula} no encontrada.");
            }

            if (!$aula->getDisponible()) {
                throw new DomainException("El aula {$aula->getNombre()} no se encuentra disponible.");
            }

            $conflictoAula = $this->horarioRepo->existeConflictoAula(
                $idAula,
                $diaSemana,
                $horaInicio,
                $horaFin
            );
            if ($conflictoAula) {
                throw new DomainException("Conflicto de aula: El aula {$aula->getNombre()} ya se encuentra ocupada el {$diaSemana} entre las {$horaInicio} y {$horaFin}.");
            }
        }

        // Fictional ID 1 for instantiation before DB insert
        $horario = new Horario(
            1,
            $idSeccion,
            $idAula,
            $diaSemana,
            $inicioObj,
            $finObj,
            $modalidad
        );

        $this->horarioRepo->guardar($horario);

        return [
            'id_seccion'  => $idSeccion,
            'id_aula'     => $idAula,
            'dia_semana'  => $diaSemana,
            'hora_inicio' => $inicioObj->format('H:i'),
            'hora_fin'    => $finObj->format('H:i'),
            'modalidad'   => $modalidad,
            'mensaje'     => 'Horario registrado exitosamente sin conflictos de aula ni docente.'
        ];
    }
}

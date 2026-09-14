<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Horario;

use App\Dominio\Repositorios\HorarioRepositorio;

/**
 * Consulta y listado de horarios asignados.
 */
class ConsultarHorariosSeccion
{
    private HorarioRepositorio $horarioRepo;

    public function __construct(HorarioRepositorio $horarioRepo)
    {
        $this->horarioRepo = $horarioRepo;
    }

    public function listarHorarios(): array
    {
        $horarios = $this->horarioRepo->listar();
        return array_map(fn($h) => [
            'id_horario'  => $h->getIdHorario(),
            'dia_semana'  => $h->getDiaSemana(),
            'hora_inicio' => $h->getHoraInicio()->format('H:i'),
            'hora_fin'    => $h->getHoraFin()->format('H:i'),
            'modalidad'   => $h->getModalidad()
        ], $horarios);
    }
}

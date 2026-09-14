<?php

namespace App\Dominio\Repositorios;

use App\Dominio\Entidades\Horario;

interface HorarioRepositorio
{
    public function buscarPorId( int $idHorario ): ?Horario;

    public function listar(): array;

    public function guardar( Horario $horario): void;

    public function actualizar( Horario $horario ): void;

    public function eliminar(int $idHorario ): void;

    public function existeConflictoAula(
        int $idAula,
        string $diaSemana,
        string $horaInicio,
        string $horaFin
    ): bool;

    public function existeConflictoDocente(
        int $idDocente,
        string $diaSemana,
        string $horaInicio,
        string $horaFin
    ): bool;
}
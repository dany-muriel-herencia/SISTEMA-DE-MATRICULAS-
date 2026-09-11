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
}
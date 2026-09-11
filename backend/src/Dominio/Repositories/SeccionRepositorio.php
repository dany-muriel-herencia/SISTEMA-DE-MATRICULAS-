<?php

namespace App\Dominio\Repositorios;

use App\Dominio\Entidades\Seccion;

interface SeccionRepositorio
{
    public function buscarPorId(
        int $idSeccion
    ): ?Seccion;

    public function listarDisponibles(): array;

    public function guardar(
        Seccion $seccion
    ): void;

    public function actualizar(
        Seccion $seccion
    ): void;
}
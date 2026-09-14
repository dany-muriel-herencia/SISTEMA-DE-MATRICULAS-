<?php

namespace App\Dominio\Repositorios;

use App\Dominio\Entidades\Escuela;

interface EscuelaRepositorio
{
    public function buscarPorId(
        int $idEscuela
    ): ?Escuela;

    public function listar(): array;

    public function guardar(
        Escuela $escuela
    ): void;

    public function actualizar(
        Escuela $escuela
    ): void;
}
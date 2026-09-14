<?php

namespace App\Dominio\Repositorios;

use App\Dominio\Entidades\Facultad;

interface FacultadRepositorio
{
    public function buscarPorId( int $idFacultad): ?Facultad;

    public function listar(): array;

    public function guardar( Facultad $facultad ): void;

    public function actualizar( Facultad $facultad ): void;
}
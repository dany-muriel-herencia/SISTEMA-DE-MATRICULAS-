<?php

namespace App\Dominio\Repositorios;

use App\Dominio\Entidades\Prerequisito;

interface PrerequisitoRepositorio
{
    public function buscarPorId( int $idPrerequisito ): ?Prerequisito;

    public function listar(): array;

    public function guardar( Prerequisito $prerequisito ): void;

    public function eliminar( int $idPrerequisito ): void;
}
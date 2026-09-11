<?php

namespace App\Dominio\Repositorios;

use App\Dominio\Entidades\Aula;

interface AulaRepositorio
{
    public function buscarPorId(
        int $idAula
    ): ?Aula;

    public function listar(): array;

    public function listarDisponibles(): array;

    public function guardar(
        Aula $aula
    ): void;

    public function actualizar(
        Aula $aula
    ): void;
}
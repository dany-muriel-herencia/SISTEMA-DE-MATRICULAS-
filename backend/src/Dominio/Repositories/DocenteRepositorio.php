<?php

namespace App\Dominio\Repositorios;

use App\Dominio\Entidades\Docente;

interface DocenteRepositorio
{
    public function buscarPorId(int $idUsuario): ?Docente;

    public function buscarPorCodigo(
        string $codigo
    ): ?Docente;

    public function guardar(
        Docente $docente
    ): void;

    public function actualizar(
        Docente $docente
    ): void;
}
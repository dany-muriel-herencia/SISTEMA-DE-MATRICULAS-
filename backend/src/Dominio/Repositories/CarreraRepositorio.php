<?php

namespace App\Dominio\Repositorios;

use App\Dominio\Entidades\Carrera;

interface CarreraRepositorio
{
    public function buscarPorId( int $idCarrera): ?Carrera;

    public function buscarPorCodigo(  string $codigo ): ?Carrera;

    public function listar(): array;

    public function guardar( Carrera $carrera ): void;

    public function actualizar( Carrera $carrera ): void;
}
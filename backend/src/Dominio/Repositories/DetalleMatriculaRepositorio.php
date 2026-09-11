<?php

namespace App\Dominio\Repositorios;

use App\Dominio\Entidades\DetalleMatricula;

interface DetalleMatriculaRepositorio
{
    public function buscarPorId( int $idDetalle): ?DetalleMatricula;

    public function listarPorMatricula( int $idMatricula ): array;

    public function guardar( DetalleMatricula $detalleMatricula): void;

    public function actualizar( DetalleMatricula $detalleMatricula ): void;

    public function eliminar( int $idDetalle): void;
}
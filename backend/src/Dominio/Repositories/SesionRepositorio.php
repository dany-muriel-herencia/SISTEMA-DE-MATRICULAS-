<?php

namespace App\Dominio\Repositorios;

use App\Dominio\Entidades\Sesion;

interface SesionRepositorio
{
    public function buscarPorId( int $idSesion ): ?Sesion;

    public function buscarPorToken( string $token ): ?Sesion;

    public function listarPorUsuario( int $idUsuario ): array;

    public function guardar( Sesion $sesion ): void;

    public function actualizar( Sesion $sesion): void;

    public function eliminar( int $idSesion ): void;
}
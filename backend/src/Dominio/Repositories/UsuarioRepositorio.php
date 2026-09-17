<?php

namespace App\Dominio\Repositorios;

use App\Dominio\Entidades\Usuario;

interface UsuarioRepositorio
{
    public function buscarPorId(int $idUsuario): ?Usuario;

    public function buscarPorEmail(string $email): ?Usuario;

    public function guardar(Usuario $usuario): void;

    public function actualizar(Usuario $usuario): void;

    public function eliminar(int $idUsuario): void;

    public function listar(int $limit = 50, int $offset = 0): array;
}
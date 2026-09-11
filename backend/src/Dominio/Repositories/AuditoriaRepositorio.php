<?php

namespace App\Dominio\Repositorios;

use App\Dominio\Entidades\Auditoria;

interface AuditoriaRepositorio
{
    public function buscarPorId( int $idAuditoria ): ?Auditoria;

    public function listar(): array;

    public function listarPorUsuario( int $idUsuario): array;

    public function guardar(  Auditoria $auditoria): void;
}
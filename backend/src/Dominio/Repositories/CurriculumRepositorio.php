<?php

namespace App\Dominio\Repositorios;

use App\Dominio\Entidades\Curriculum;

interface CurriculumRepositorio
{
    public function buscarPorId( int $idCurriculum): ?Curriculum;

    public function listar(): array;

    public function guardar( Curriculum $curriculum ): void;

    public function actualizar( Curriculum $curriculum ): void;
}
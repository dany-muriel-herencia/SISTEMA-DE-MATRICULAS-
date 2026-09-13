<?php

namespace App\Dominio\Repositorios;

use App\Dominio\Entidades\Matricula;

interface MatriculaRepositorio
{
    public function buscarPorId(int $idMatricula): ?Matricula;

    public function buscarPorEstudiante(int $idEstudiante): array;

    public function buscarPorPeriodo(int $idPeriodo): array;

    public function guardar(Matricula $matricula): void;

    public function actualizar(Matricula $matricula): void;
}
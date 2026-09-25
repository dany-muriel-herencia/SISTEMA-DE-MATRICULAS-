<?php

namespace App\Dominio\Repositorios;

use App\Dominio\Entidades\Estudiante;

interface EstudianteRepositorio
{
    public function buscarPorId(int $idUsuario): ?Estudiante;

    public function buscarPorCodigo(
        string $codigoUniversitario
    ): ?Estudiante;

    public function buscarPorDni(
        string $dni
    ): ?Estudiante;

    public function guardar(
        Estudiante $estudiante
    ): void;

    public function actualizar(
        Estudiante $estudiante
    ): void;
    public function listar(int $limit = 50, int $offset = 0): array;

}

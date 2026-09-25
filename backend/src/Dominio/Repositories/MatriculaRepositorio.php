<?php

namespace App\Dominio\Repositorios;

use App\Dominio\Entidades\Matricula;
use App\Dominio\Entidades\DetalleMatricula;

interface MatriculaRepositorio
{
    public function buscarPorId(int $idMatricula): ?Matricula;

    public function buscarPorEstudiante(int $idEstudiante): array;

    public function buscarPorPeriodo(int $idPeriodo): array;

    public function buscarPorCodigo(string $codigo): ?Matricula;

    public function listarPorEstudiante(int $idEstudiante): array;

    public function guardar(Matricula $matricula): void;

    public function actualizar(Matricula $matricula): void;

    /** @param DetalleMatricula[] $detalles */
    public function registrarConDetalles(Matricula $matricula, array $detalles): int;
    public function anularConDetalles(int $idMatricula): void;

}

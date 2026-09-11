<?php

namespace App\Dominio\Repositorios;

use App\Dominio\Entidades\Curso;

interface CursoRepositorio
{
    public function buscarPorId(int $idCurso): ?Curso;

    public function buscarPorCodigo(string $codigo): ?Curso;

    public function listarActivos(): array;

    public function guardar(Curso $curso): void;

    public function actualizar(Curso $curso): void;
}
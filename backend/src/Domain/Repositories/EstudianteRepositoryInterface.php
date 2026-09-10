<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Estudiante;

interface EstudianteRepositoryInterface
{
    public function guardar(Estudiante $estudiante): int;
    public function actualizar(Estudiante $estudiante): bool;
    public function buscarPorId(int $id): ?Estudiante;
    public function buscarPorUsuarioId(int $usuarioId): ?Estudiante;
    public function buscarPorCodigo(string $codigo): ?Estudiante;
    public function listar(int $limit = 50, int $offset = 0): array;
    public function obtenerHistorialCursosAprobados(int $estudianteId): array;
}

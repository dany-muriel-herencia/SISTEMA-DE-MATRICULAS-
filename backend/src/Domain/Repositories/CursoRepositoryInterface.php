<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Curso;
use App\Domain\Entities\Seccion;

interface CursoRepositoryInterface
{
    public function guardar(Curso $curso): int;
    public function actualizar(Curso $curso): bool;
    public function buscarPorId(int $id): ?Curso;
    public function buscarPorCodigo(string $codigo): ?Curso;
    public function listar(int $limit = 50, int $offset = 0): array;
    public function obtenerPrerrequisitos(int $planEstudioId, int $cursoId): array;
    public function buscarSeccionPorId(int $seccionId): ?Seccion;
    public function listarOfertaPorPeriodoYCarrera(int $periodoId, int $carreraId): array;
}

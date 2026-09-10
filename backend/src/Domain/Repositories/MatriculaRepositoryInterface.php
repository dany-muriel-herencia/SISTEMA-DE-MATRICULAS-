<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Matricula;
use App\Domain\Entities\MatriculaDetalle;

interface MatriculaRepositoryInterface
{
    public function guardar(Matricula $matricula): int;
    public function guardarDetalle(MatriculaDetalle $detalle): int;
    public function buscarPorId(int $id): ?Matricula;
    public function buscarPorCodigo(string $codigo): ?Matricula;
    public function buscarPorEstudianteYPeriodo(int $estudianteId, int $periodoId): ?Matricula;
    public function anular(int $id): bool;
    public function verificarCupoSeccionConBloqueo(int $seccionId): int;
    public function decrementarCupoSeccion(int $seccionId): bool;
    public function incrementarCupoSeccion(int $seccionId): bool;
    public function verificarCruceHorarios(array $seccionIds): array;
    public function listarPorEstudiante(int $estudianteId): array;
    public function listarPorPeriodo(int $periodoId, int $limit = 50, int $offset = 0): array;
}

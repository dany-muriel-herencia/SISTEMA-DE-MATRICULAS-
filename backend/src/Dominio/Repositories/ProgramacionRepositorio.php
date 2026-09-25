<?php
declare(strict_types=1);
namespace App\Dominio\Repositorios;

interface ProgramacionRepositorio
{
    public function catalogos(): array;
    public function listar(int $periodoId, ?int $cursoId): array;
    public function consultar(int $id): ?array;
    public function crearSeccion(array $datos): array;
    public function crearHorario(int $seccionId, array $datos): array;
}

<?php

declare(strict_types=1);

namespace App\Application\UseCases\Estudiante;

use App\Domain\Repositories\EstudianteRepositoryInterface;

class ListarEstudiantes
{
    private EstudianteRepositoryInterface $estudianteRepo;

    public function __construct(EstudianteRepositoryInterface $estudianteRepo)
    {
        $this->estudianteRepo = $estudianteRepo;
    }

    public function ejecutar(int $limit = 50, int $offset = 0): array
    {
        return $this->estudianteRepo->listar($limit, $offset);
    }
}

<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Estudiante;

use App\Dominio\Repositorios\EstudianteRepositorio;

class ListarEstudiantes
{
    private EstudianteRepositorio $estudianteRepo;

    public function __construct(EstudianteRepositorio $estudianteRepo)
    {
        $this->estudianteRepo = $estudianteRepo;
    }

    public function ejecutar(int $limit = 50, int $offset = 0): array
    {
        return $this->estudianteRepo->listar($limit, $offset);
    }
}

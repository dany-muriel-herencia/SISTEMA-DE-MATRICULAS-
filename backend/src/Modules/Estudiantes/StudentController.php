<?php

declare(strict_types=1);

namespace App\Modules\Estudiantes;

use App\Core\ApiResponse;

final class StudentController
{
    private StudentRepository $repo;

    public function __construct()
    {
        $this->repo = new StudentRepository();
    }

    public function listar(): void
    {
        ApiResponse::success($this->repo->listarEstudiantes());
    }

    public function obtenerKardex(string $idEstudiante): void
    {
        $data = $this->repo->obtenerKardex((int)$idEstudiante);
        if (!$data) {
            ApiResponse::error('Estudiante no encontrado.', 404, 'ESTUDIANTE_NO_ENCONTRADO');
            return;
        }
        ApiResponse::success($data);
    }

    public function obtenerFichaMedica(string $idEstudiante): void
    {
        $data = $this->repo->obtenerFichaMedica((int)$idEstudiante);
        ApiResponse::success($data);
    }
}

<?php

declare(strict_types=1);

namespace App\Application\UseCases\Docente;

use App\Domain\Entities\Docente;
use App\Domain\Repositories\DocenteRepositoryInterface;
use App\Domain\Repositories\UsuarioRepositoryInterface;
use DomainException;
use RuntimeException;

class CrearDocente
{
    private DocenteRepositoryInterface $docenteRepo;
    private UsuarioRepositoryInterface $usuarioRepo;

    public function __construct(
        DocenteRepositoryInterface $docenteRepo,
        UsuarioRepositoryInterface $usuarioRepo
    ) {
        $this->docenteRepo = $docenteRepo;
        $this->usuarioRepo = $usuarioRepo;
    }

    public function ejecutar(int $usuarioId, ?string $especialidad = null, ?string $gradoAcademico = null): Docente
    {
        $usuario = $this->usuarioRepo->buscarPorId($usuarioId);
        if (!$usuario) {
            throw new DomainException("El usuario con ID {$usuarioId} no existe.");
        }

        if ($this->docenteRepo->buscarPorUsuarioId($usuarioId)) {
            throw new DomainException("El usuario ya tiene un perfil docente registrado.");
        }

        $docente = new Docente($usuarioId, $especialidad, $gradoAcademico, $usuario);
        $id = $this->docenteRepo->guardar($docente);
        if ($id <= 0) {
            throw new RuntimeException("No se pudo registrar el perfil docente.");
        }
        $docente = new Docente($usuarioId, $especialidad, $gradoAcademico, $usuario, $id);

        return $docente;
    }
}

<?php

declare(strict_types=1);

namespace App\Application\UseCases\Curso;

use App\Application\DTO\CrearCursoDTO;
use App\Domain\Entities\Curso;
use App\Domain\Repositories\CursoRepositoryInterface;
use DomainException;
use RuntimeException;

class CrearCurso
{
    private CursoRepositoryInterface $cursoRepo;

    public function __construct(CursoRepositoryInterface $cursoRepo)
    {
        $this->cursoRepo = $cursoRepo;
    }

    public function ejecutar(CrearCursoDTO $dto): Curso
    {
        if ($this->cursoRepo->buscarPorCodigo($dto->getCodigo())) {
            throw new DomainException("El curso con código '{$dto->getCodigo()}' ya existe.");
        }

        $curso = new Curso(
            $dto->getCodigo(),
            $dto->getNombre(),
            $dto->getCreditos(),
            $dto->getHorasTeoricas(),
            $dto->getHorasPracticas()
        );

        $id = $this->cursoRepo->guardar($curso);
        if ($id <= 0) {
            throw new RuntimeException("No se pudo guardar el curso.");
        }
        $curso->setId($id);

        return $curso;
    }
}

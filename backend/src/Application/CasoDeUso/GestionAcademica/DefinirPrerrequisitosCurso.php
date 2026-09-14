<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\GestionAcademica;

use App\Dominio\Entidades\Prerequisito;
use App\Dominio\Repositorios\CursoRepositorio;
use App\Dominio\Repositorios\PrerequisitoRepositorio;
use DomainException;
use InvalidArgumentException;

/**
 * CU-16: Definir los prerrequisitos de cada curso.
 * Actor Principal: Administrador.
 */
class DefinirPrerrequisitosCurso
{
    private PrerequisitoRepositorio $prerequisitoRepo;
    private CursoRepositorio $cursoRepo;

    public function __construct(
        PrerequisitoRepositorio $prerequisitoRepo,
        CursoRepositorio $cursoRepo
    ) {
        $this->prerequisitoRepo = $prerequisitoRepo;
        $this->cursoRepo       = $cursoRepo;
    }

    public function asignarPrerrequisito(int $idCurso, int $idCursoRequerido): array
    {
        if ($idCurso <= 0 || $idCursoRequerido <= 0) {
            throw new InvalidArgumentException('Los IDs de curso deben ser enteros positivos.');
        }

        if ($idCurso === $idCursoRequerido) {
            throw new InvalidArgumentException('Un curso no puede ser prerrequisito de sí mismo.');
        }

        $cursoTarget = $this->cursoRepo->buscarPorId($idCurso);
        if ($cursoTarget === null) {
            throw new DomainException("El curso destino con ID {$idCurso} no existe.");
        }

        $cursoRequerido = $this->cursoRepo->buscarPorId($idCursoRequerido);
        if ($cursoRequerido === null) {
            throw new DomainException("El curso prerrequisito con ID {$idCursoRequerido} no existe.");
        }

        // Fictional ID 1 for instantiation before DB insert
        $prerequisito = new Prerequisito(1, $idCurso, $idCursoRequerido);
        $this->prerequisitoRepo->guardar($prerequisito);

        return [
            'id_curso'            => $idCurso,
            'nombre_curso'        => $cursoTarget->getNombre(),
            'id_curso_requerido'  => $idCursoRequerido,
            'nombre_prerrequisito'=> $cursoRequerido->getNombre(),
            'mensaje'             => 'Prerrequisito asignado correctamente al curso.'
        ];
    }

    public function listarPrerrequisitos(): array
    {
        $lista = $this->prerequisitoRepo->listar();
        return array_map(fn($p) => [
            'id_prerequisito'   => $p->getIdPrerequisito(),
            'id_curso'          => $p->getIdCurso(),
            'id_curso_requerido'=> $p->getIdCursoRequerido()
        ], $lista);
    }
}

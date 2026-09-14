<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\GestionAcademica;

use App\Dominio\Entidades\Curso;
use App\Dominio\Repositorios\CursoRepositorio;
use DomainException;
use InvalidArgumentException;

/**
 * CU-14: Registrar cursos y su cantidad de créditos.
 * CU-15: Clasificar cursos como obligatorios o electivos.
 * Actor Principal: Administrador.
 */
class GestionarCursoAcademico
{
    private CursoRepositorio $cursoRepo;

    public function __construct(CursoRepositorio $cursoRepo)
    {
        $this->cursoRepo = $cursoRepo;
    }

    public function registrarCurso(
        string $nombre,
        string $codigo,
        int $creditos,
        int $horasTeoria = 2,
        int $horasPractica = 2,
        string $ciclo = 'I',
        string $tipo = 'OBLIGATORIO'
    ): array {
        if (empty(trim($nombre))) {
            throw new InvalidArgumentException('El nombre del curso es obligatorio.');
        }

        if (empty(trim($codigo))) {
            throw new InvalidArgumentException('El código del curso es obligatorio.');
        }

        if ($creditos <= 0) {
            throw new InvalidArgumentException('La cantidad de créditos debe ser mayor a 0.');
        }

        $tipoUpper = strtoupper(trim($tipo));
        if ($tipoUpper !== 'OBLIGATORIO' && $tipoUpper !== 'ELECTIVO') {
            throw new InvalidArgumentException('El tipo de curso debe ser OBLIGATORIO o ELECTIVO.');
        }

        $existente = $this->cursoRepo->buscarPorCodigo($codigo);
        if ($existente !== null) {
            throw new DomainException("Ya existe un curso registrado con el código {$codigo}.");
        }

        // Pasamos 1 como id ficticio para instanciar la entidad en nuevos registros
        $curso = new Curso(
            1,
            $nombre,
            $codigo,
            $creditos,
            $horasTeoria,
            $horasPractica,
            $ciclo,
            true
        );

        $this->cursoRepo->guardar($curso);

        return [
            'nombre'         => $nombre,
            'codigo'         => $codigo,
            'creditos'       => $creditos,
            'horas_teoria'   => $horasTeoria,
            'horas_practica' => $horasPractica,
            'ciclo'          => $ciclo,
            'tipo'           => $tipoUpper,
            'estado'         => true,
            'mensaje'        => 'Curso académico registrado y clasificado correctamente.'
        ];
    }

    public function listarCursos(): array
    {
        $cursos = $this->cursoRepo->listar();
        return array_map(fn($c) => [
            'id_curso'       => $c->getIdCurso(),
            'nombre'         => $c->getNombre(),
            'codigo'         => $c->getCodigo(),
            'creditos'       => $c->getCreditos(),
            'horas_teoria'   => $c->getHorasTeoria(),
            'horas_practica' => $c->getHorasPractica(),
            'ciclo'          => $c->getCiclo(),
            'estado'         => $c->getEstado()
        ], $cursos);
    }
}

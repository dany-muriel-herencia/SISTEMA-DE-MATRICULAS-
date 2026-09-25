<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Matricula;

use App\Application\DTO\RegistrarMatriculaDTO;
use App\Dominio\Entidades\DetalleMatricula;
use App\Dominio\Entidades\Matricula;
use App\Dominio\Repositorios\CursoRepositorio;
use App\Dominio\Repositorios\EstudianteRepositorio;
use App\Dominio\Repositorios\MatriculaRepositorio;
use App\Dominio\Repositorios\PeriodoAcademicoRepositorio;
use App\Dominio\Repositorios\SeccionRepositorio;
use App\Dominio\ValueObjects\CodigoMatricula;
use DomainException;
use RuntimeException;

class RegistrarMatricula
{
    private MatriculaRepositorio $matriculaRepo;
    private EstudianteRepositorio $estudianteRepo;
    private PeriodoAcademicoRepositorio $periodoRepo;
    private CursoRepositorio $cursoRepo;
    private SeccionRepositorio $seccionRepo;

    public function __construct(
        MatriculaRepositorio $matriculaRepo,
        EstudianteRepositorio $estudianteRepo,
        PeriodoAcademicoRepositorio $periodoRepo,
        CursoRepositorio $cursoRepo,
        SeccionRepositorio $seccionRepo
    ) {
        $this->matriculaRepo = $matriculaRepo;
        $this->estudianteRepo = $estudianteRepo;
        $this->periodoRepo = $periodoRepo;
        $this->cursoRepo = $cursoRepo;
        $this->seccionRepo = $seccionRepo;
    }

    public function ejecutar(RegistrarMatriculaDTO $dto): Matricula
    {
        // 1. Validar existencia del estudiante y estado académico
        $estudiante = $this->estudianteRepo->buscarPorId($dto->getEstudianteId());
        if (!$estudiante) {
            throw new DomainException("El estudiante con ID {$dto->getEstudianteId()} no existe.");
        }
        if (!$estudiante->estaActivo()) {
            throw new DomainException("El estudiante no está habilitado para matricularse.");
        }

        // 2. Validar periodo académico
        $periodo = $this->periodoRepo->buscarPorId($dto->getPeriodoId());
        if (!$periodo) {
            throw new DomainException("El periodo académico con ID {$dto->getPeriodoId()} no existe.");
        }
        $ahora = new \DateTimeImmutable('now');
        if ($periodo->getEstado() !== 'MATRICULA_ABIERTA' || $ahora < $periodo->getFechaMatriculaInicio() || $ahora >= $periodo->getFechaMatriculaFin()->modify('+1 day')) {
            throw new DomainException("El periodo académico {$periodo->getNombre()} no tiene el proceso de matrícula abierto actualmente.");
        }

        // 3. Validar si ya cuenta con matrícula en el periodo
        foreach ($this->matriculaRepo->buscarPorEstudiante($estudiante->getIdUsuario()) as $matriculaExistente) {
            if ($matriculaExistente->getIdPeriodo() === $periodo->getIdPeriodo() && $matriculaExistente->getEstado() === 'REGISTRADA') {
                throw new DomainException('El estudiante ya cuenta con una matrícula activa en este periodo.');
            }
        }

        // 4. Validar existencia de las secciones seleccionadas
        $secciones = [];
        $cursosSolicitados = [];
        foreach ($dto->getSecciones() as $seccionId) {
            $seccion = $this->seccionRepo->buscarPorId($seccionId);
            if (!$seccion) {
                throw new DomainException("La sección con ID {$seccionId} no existe.");
            }
            if ($seccion->getIdPeriodo() !== $periodo->getIdPeriodo()) {
                throw new DomainException("La sección {$seccion->getCodigo()} no pertenece al periodo {$periodo->getNombre()}.");
            }
            if (isset($cursosSolicitados[$seccion->getIdCurso()])) {
                throw new DomainException("No puede matricularse en dos secciones del mismo curso.");
            }
            $cursosSolicitados[$seccion->getIdCurso()] = true;
            $secciones[] = $seccion;
        }

        // 8. Crear entidad Matrícula
        $codigoMatricula = CodigoMatricula::generar($periodo->getIdPeriodo(), $estudiante->getIdUsuario());
        $totalCreditos = 0;
        foreach ($secciones as $seccion) {
            $curso = $this->cursoRepo->buscarPorId($seccion->getIdCurso());
            if (!$curso) {
                throw new DomainException("El curso de la sección {$seccion->getCodigo()} no existe.");
            }
            $totalCreditos += $curso->getCreditos();
        }

        if ($totalCreditos <= 0 || $totalCreditos > 22) {
            throw new DomainException("El total de créditos ({$totalCreditos}) debe estar entre 1 y 22.");
        }

        $matricula = new Matricula(0, $estudiante->getIdUsuario(), $periodo->getIdPeriodo(), new \DateTimeImmutable('now'), 'REGISTRADA', $totalCreditos);
        $matricula->setCodigoMatricula($codigoMatricula);
        $detalles = array_map(
                fn($seccion): DetalleMatricula => new DetalleMatricula(0, 0, $seccion->getIdSeccion(), 'MATRICULADO'),
            $secciones
        );

        $matriculaId = $this->matriculaRepo->registrarConDetalles($matricula, $detalles);
        if ($matriculaId <= 0) {
            throw new RuntimeException("Error al guardar la matrícula en la base de datos.");
        }
        $matricula->setIdMatricula($matriculaId);
        $matricula->setDetalles(array_map(
            fn(DetalleMatricula $detalle): DetalleMatricula => new DetalleMatricula($detalle->getIdDetalle(), $matriculaId, $detalle->getIdSeccion(), $detalle->getEstado()),
            $detalles
        ));

        return $matricula;
    }
}

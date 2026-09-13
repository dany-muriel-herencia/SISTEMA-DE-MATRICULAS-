<?php

declare(strict_types=1);

namespace App\Application\UseCases\Matricula;

use App\Application\DTO\RegistrarMatriculaDTO;
use App\Domain\Entities\Matricula;
use App\Domain\Entities\MatriculaDetalle;
use App\Domain\Repositories\CursoRepositoryInterface;
use App\Domain\Repositories\EstudianteRepositoryInterface;
use App\Domain\Repositories\MatriculaRepositoryInterface;
use App\Domain\Repositories\PeriodoAcademicoRepositoryInterface;
use App\Domain\ValueObjects\CodigoMatricula;
use DomainException;
use RuntimeException;

class RegistrarMatricula
{
    private MatriculaRepositoryInterface $matriculaRepo;
    private EstudianteRepositoryInterface $estudianteRepo;
    private PeriodoAcademicoRepositoryInterface $periodoRepo;
    private CursoRepositoryInterface $cursoRepo;

    public function __construct(
        MatriculaRepositoryInterface $matriculaRepo,
        EstudianteRepositoryInterface $estudianteRepo,
        PeriodoAcademicoRepositoryInterface $periodoRepo,
        CursoRepositoryInterface $cursoRepo
    ) {
        $this->matriculaRepo = $matriculaRepo;
        $this->estudianteRepo = $estudianteRepo;
        $this->periodoRepo = $periodoRepo;
        $this->cursoRepo = $cursoRepo;
    }

    public function ejecutar(RegistrarMatriculaDTO $dto): Matricula
    {
        // 1. Validar existencia del estudiante y estado académico
        $estudiante = $this->estudianteRepo->buscarPorId($dto->getEstudianteId());
        if (!$estudiante) {
            throw new DomainException("El estudiante con ID {$dto->getEstudianteId()} no existe.");
        }
        if (!$estudiante->puedeMatricularse()) {
            throw new DomainException("El estudiante no está habilitado para matricularse (Estado: {$estudiante->getEstadoAcademico()}).");
        }

        // 2. Validar periodo académico
        $periodo = $this->periodoRepo->buscarPorId($dto->getPeriodoId());
        if (!$periodo) {
            throw new DomainException("El periodo académico con ID {$dto->getPeriodoId()} no existe.");
        }
        if (!$periodo->estaEnPeriodoMatricula()) {
            throw new DomainException("El periodo académico {$periodo->getCodigo()} no tiene el proceso de matrícula abierto actualmente.");
        }

        // 3. Validar si ya cuenta con matrícula en el periodo
        $matriculaExistente = $this->matriculaRepo->buscarPorEstudianteYPeriodo($estudiante->getId(), $periodo->getId());
        if ($matriculaExistente && $matriculaExistente->esValida()) {
            throw new DomainException("El estudiante ya cuenta con una matrícula activa ({$matriculaExistente->getCodigoMatricula()}) en el periodo {$periodo->getCodigo()}.");
        }

        // 4. Validar existencia de las secciones seleccionadas
        $secciones = [];
        $cursosSolicitados = [];
        foreach ($dto->getSecciones() as $seccionId) {
            $seccion = $this->cursoRepo->buscarSeccionPorId($seccionId);
            if (!$seccion) {
                throw new DomainException("La sección con ID {$seccionId} no existe.");
            }
            if ($seccion->getPeriodoId() !== $periodo->getId()) {
                throw new DomainException("La sección {$seccion->getLetraSeccion()} no pertenece al periodo {$periodo->getCodigo()}.");
            }
            if (isset($cursosSolicitados[$seccion->getCursoId()])) {
                throw new DomainException("No puede matricularse en dos secciones del mismo curso.");
            }
            $cursosSolicitados[$seccion->getCursoId()] = true;
            $secciones[] = $seccion;
        }

        // 5. Validar prerrequisitos
        $cursosAprobados = $this->estudianteRepo->obtenerHistorialCursosAprobados($estudiante->getId());
        foreach ($secciones as $sec) {
            $prerrequisitos = $this->cursoRepo->obtenerPrerrequisitos($estudiante->getPlanEstudioId(), $sec->getCursoId());
            foreach ($prerrequisitos as $req) {
                if (!in_array($req['curso_requisito_id'], $cursosAprobados, true)) {
                    throw new DomainException("No cumple con el prerrequisito '{$req['curso_requisito_nombre']}' ({$req['curso_requisito_codigo']}) para el curso '{$sec->getCurso()->getNombre()}'.");
                }
            }
        }

        // 6. Validar cruces de horarios
        $cruces = $this->matriculaRepo->verificarCruceHorarios($dto->getSecciones());
        if (!empty($cruces)) {
            $cruce = $cruces[0];
            throw new DomainException("Existe un cruce de horario entre las secciones seleccionadas: Día {$cruce['dia_semana']} ({$cruce['hora_inicio']} - {$cruce['hora_fin']}).");
        }

        // 7. Validar vacantes disponibles
        foreach ($secciones as $sec) {
            $vacantesActuales = $this->matriculaRepo->verificarCupoSeccionConBloqueo($sec->getId());
            if ($vacantesActuales <= 0) {
                throw new DomainException("No hay vacantes disponibles para la sección {$sec->getLetraSeccion()} del curso {$sec->getCurso()->getNombre()}.");
            }
        }

        // 8. Crear entidad Matrícula
        $codigoMatricula = CodigoMatricula::generar($periodo->getId(), $estudiante->getId());
        $matricula = new Matricula(
            $estudiante->getId(),
            $periodo->getId(),
            $codigoMatricula,
            date('Y-m-d H:i:s'),
            0,
            'REGISTRADA',
            [],
            $estudiante,
            $periodo
        );

        foreach ($secciones as $sec) {
            $detalle = new MatriculaDetalle(
                0,
                $sec->getId(),
                $sec->getCurso()->getCreditos(),
                'MATRICULADO',
                $sec
            );
            $matricula->agregarDetalle($detalle);
        }

        // 9. Validar límites de créditos
        if (!$matricula->validarLimiteCreditos()) {
            throw new DomainException("El total de créditos ({$matricula->getTotalCreditos()}) excede el límite permitido (1 a " . Matricula::MAX_CREDITOS_PERMITIDOS . " créditos).");
        }

        // 10. Persistir matrícula y decrementar vacantes
        $matriculaId = $this->matriculaRepo->guardar($matricula);
        if ($matriculaId <= 0) {
            throw new RuntimeException("Error al guardar la matrícula en la base de datos.");
        }
        $matricula->setId($matriculaId);

        foreach ($matricula->getDetalles() as $detalle) {
            $detalle->setMatriculaId($matriculaId);
            $this->matriculaRepo->guardarDetalle($detalle);
            $this->matriculaRepo->decrementarCupoSeccion($detalle->getSeccionId());
        }

        return $matricula;
    }
}

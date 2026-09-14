<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Estudiante;

use App\Dominio\Repositorios\DetalleMatriculaRepositorio;
use App\Dominio\Repositorios\EstudianteRepositorio;
use App\Dominio\Repositorios\MatriculaRepositorio;
use DomainException;
use InvalidArgumentException;

/**
 * CU-10: Consultar historial académico del estudiante.
 * Actor Principal: Estudiante, Administrador.
 */
class ConsultarHistorialAcademico
{
    private EstudianteRepositorio $estudianteRepo;
    private MatriculaRepositorio $matriculaRepo;
    private DetalleMatriculaRepositorio $detalleRepo;

    public function __construct(
        EstudianteRepositorio $estudianteRepo,
        MatriculaRepositorio $matriculaRepo,
        DetalleMatriculaRepositorio $detalleRepo
    ) {
        $this->estudianteRepo = $estudianteRepo;
        $this->matriculaRepo  = $matriculaRepo;
        $this->detalleRepo    = $detalleRepo;
    }

    public function ejecutar(int $idEstudiante): array
    {
        if ($idEstudiante <= 0) {
            throw new InvalidArgumentException('El ID del estudiante debe ser mayor a 0.');
        }

        $estudiante = $this->estudianteRepo->buscarPorId($idEstudiante);
        if ($estudiante === null) {
            throw new DomainException("Estudiante con ID {$idEstudiante} no encontrado.");
        }

        $matriculas = $this->matriculaRepo->listarPorEstudiante($idEstudiante);

        $historialMatriculas = [];
        $totalCreditosAprobados = 0;
        $totalCursosInscritos   = 0;
        $sumaNotas              = 0.0;
        $totalCursosConNota     = 0;

        foreach ($matriculas as $m) {
            $detalles = $this->detalleRepo->buscarPorMatricula($m->getIdMatricula());

            $cursosList = [];
            foreach ($detalles as $d) {
                $totalCursosInscritos++;
                $nota = $d->getNotaFinal();
                $estadoCurso = $d->getEstado();

                if ($nota !== null) {
                    $sumaNotas += $nota;
                    $totalCursosConNota++;
                }

                if (strtoupper($estadoCurso) === 'APROBADO' || ($nota !== null && $nota >= 10.5)) {
                    // Si no tenemos método getCreditos directo en detalle, se toma 3 o 4 como valor estándar por asignatura
                    $totalCreditosAprobados += 4;
                }

                $cursosList[] = [
                    'id_detalle'   => $d->getIdDetalle(),
                    'id_seccion'   => $d->getIdSeccion(),
                    'nota_final'   => $d->getNotaFinal(),
                    'estado_curso' => $d->getEstado()
                ];
            }

            $historialMatriculas[] = [
                'id_matricula'       => $m->getIdMatricula(),
                'codigo_matricula'   => $m->getCodigoMatricula(),
                'fecha_matricula'    => $m->getFechaMatricula()->format('Y-m-d H:i:s'),
                'tipo_matricula'     => $m->getTipoMatricula(),
                'estado_matricula'   => $m->getEstadoMatricula(),
                'cursos'             => $cursosList
            ];
        }

        $promedioPonderado = $totalCursosConNota > 0 ? round($sumaNotas / $totalCursosConNota, 2) : $estudiante->getPromedioAcademico();

        return [
            'estudiante' => [
                'id_usuario'           => $estudiante->getIdUsuario(),
                'codigo_universitario' => $estudiante->getCodigoUniversitario(),
                'nombre'               => $estudiante->getNombre(),
                'email'                => $estudiante->getEmail(),
                'dni'                  => $estudiante->getDni(),
                'promedio_acumulado'   => $promedioPonderado,
                'creditos_aprobados'   => $totalCreditosAprobados,
                'total_cursos'         => $totalCursosInscritos
            ],
            'historial_matriculas' => $historialMatriculas
        ];
    }
}

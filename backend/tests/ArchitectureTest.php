<?php

declare(strict_types=1);

namespace Tests;

use App\Application\DTO\RegistrarMatriculaDTO;
use App\Application\UseCases\Matricula\RegistrarMatricula;
use App\Domain\Entities\Curso;
use App\Domain\Entities\Estudiante;
use App\Domain\Entities\Matricula;
use App\Domain\Entities\PeriodoAcademico;
use App\Domain\Entities\Seccion;
use App\Domain\Entities\Usuario;
use App\Domain\Repositories\CursoRepositoryInterface;
use App\Domain\Repositories\EstudianteRepositoryInterface;
use App\Domain\Repositories\MatriculaRepositoryInterface;
use App\Domain\Repositories\PeriodoAcademicoRepositoryInterface;
use App\Domain\ValueObjects\CodigoMatricula;
use App\Domain\ValueObjects\Dni;
use App\Domain\ValueObjects\Email;
use DomainException;
use InvalidArgumentException;

class ArchitectureTest
{
    public static function runAll(): void
    {
        echo "========================================\n";
        echo " EJECUTANDO TESTS DE CLEAN ARCHITECTURE \n";
        echo "========================================\n\n";

        self::testValueObjects();
        self::testEntities();
        self::testMatriculaRules();
        self::testRegistrarMatriculaUseCase();

        echo "\n[PASS] Todos los tests de arquitectura pasaron exitosamente!\n";
    }

    private static function testValueObjects(): void
    {
        echo "[TEST] Value Objects: Dni, Email, CodigoMatricula... ";

        $dni = new Dni("72345678");
        assert($dni->getValue() === "72345678");

        $email = new Email("ESTUDIANTE@unjbg.edu.pe");
        assert($email->getValue() === "estudiante@unjbg.edu.pe");

        $cod = new CodigoMatricula("MAT-2026-001");
        assert($cod->getValue() === "MAT-2026-001");

        // Invalid DNI
        try {
            new Dni("123");
            assert(false, "Dni inválido no debería pasar");
        } catch (InvalidArgumentException) {
            // Expected
        }

        echo "OK\n";
    }

    private static function testEntities(): void
    {
        echo "[TEST] Entidades de Dominio: Usuario, Estudiante, Curso, Periodo... ";

        $usuario = new Usuario(
            3,
            new Dni("12345678"),
            new Email("test@unjbg.edu.pe"),
            "hash",
            "Carlos",
            "Ramos",
            "987654321",
            true,
            1
        );
        assert($usuario->getNombreCompleto() === "Carlos Ramos");

        $estudiante = new Estudiante(
            1,
            1,
            1,
            "2024-119001",
            2024,
            "REGULAR",
            $usuario,
            1
        );
        assert($estudiante->puedeMatricularse() === true);

        $curso = new Curso("IS-101", "Programación I", 4, 2, 2, 1);
        assert($curso->getTotalHoras() === 4);

        $periodo = new PeriodoAcademico(
            "2026-I",
            2026,
            "I",
            "2026-03-01",
            "2026-07-30",
            "2026-01-01 00:00:00",
            "2030-12-31 23:59:59",
            "MATRICULA_ABIERTA",
            1
        );
        assert($periodo->estaEnPeriodoMatricula() === true);

        echo "OK\n";
    }

    private static function testMatriculaRules(): void
    {
        echo "[TEST] Reglas de Dominio de Matrícula (Límite créditos y estados)... ";

        $matricula = new Matricula(
            1,
            1,
            new CodigoMatricula("MAT-1-1-TEST"),
            date('Y-m-d H:i:s'),
            20,
            "REGISTRADA"
        );

        assert($matricula->validarLimiteCreditos() === true);
        assert($matricula->esValida() === true);

        echo "OK\n";
    }

    private static function testRegistrarMatriculaUseCase(): void
    {
        echo "[TEST] Caso de Uso: RegistrarMatricula con Mocks de Repositorios... ";

        // Repositorios Mock en memoria para verificar Clean Architecture sin base de datos
        $estudianteRepo = new class implements EstudianteRepositoryInterface {
            public function guardar(Estudiante $e): int { return 1; }
            public function actualizar(Estudiante $e): bool { return true; }
            public function buscarPorId(int $id): ?Estudiante {
                $u = new Usuario(3, new Dni("87654321"), new Email("alumno@unjbg.edu.pe"), "hash", "Alum", "No", null, true, $id);
                return new Estudiante($id, 1, 1, "2024-001", 2024, "REGULAR", $u, $id);
            }
            public function buscarPorUsuarioId(int $uId): ?Estudiante { return null; }
            public function buscarPorCodigo(string $c): ?Estudiante { return null; }
            public function listar(int $l = 50, int $o = 0): array { return []; }
            public function obtenerHistorialCursosAprobados(int $eId): array { return [10]; }
        };

        $periodoRepo = new class implements PeriodoAcademicoRepositoryInterface {
            public function guardar(PeriodoAcademico $p): int { return 1; }
            public function buscarPorId(int $id): ?PeriodoAcademico {
                return new PeriodoAcademico("2026-I", 2026, "I", "2026-03-01", "2026-07-31", "2026-01-01 00:00:00", "2030-12-31 23:59:59", "MATRICULA_ABIERTA", $id);
            }
            public function buscarPorCodigo(string $c): ?PeriodoAcademico { return null; }
            public function obtenerPeriodoActivo(): ?PeriodoAcademico { return null; }
            public function listar(int $l = 50, int $o = 0): array { return []; }
        };

        $cursoRepo = new class implements CursoRepositoryInterface {
            public function guardar(Curso $c): int { return 1; }
            public function actualizar(Curso $c): bool { return true; }
            public function buscarPorId(int $id): ?Curso { return null; }
            public function buscarPorCodigo(string $c): ?Curso { return null; }
            public function listar(int $l = 50, int $o = 0): array { return []; }
            public function obtenerPrerrequisitos(int $planId, int $cursoId): array { return []; }
            public function buscarSeccionPorId(int $secId): ?Seccion {
                $curso = new Curso("IS-201", "Algoritmos", 4, 2, 2, 100);
                return new Seccion(1, 100, "A", 40, 20, 1, $curso, null, [], $secId);
            }
            public function listarOfertaPorPeriodoYCarrera(int $pId, int $cId): array { return []; }
        };

        $matriculaRepo = new class implements MatriculaRepositoryInterface {
            public function guardar(Matricula $m): int { return 99; }
            public function guardarDetalle(\App\Domain\Entities\MatriculaDetalle $d): int { return 1; }
            public function buscarPorId(int $id): ?Matricula { return null; }
            public function buscarPorCodigo(string $c): ?Matricula { return null; }
            public function buscarPorEstudianteYPeriodo(int $eId, int $pId): ?Matricula { return null; }
            public function anular(int $id): bool { return true; }
            public function verificarCupoSeccionConBloqueo(int $secId): int { return 15; }
            public function decrementarCupoSeccion(int $secId): bool { return true; }
            public function incrementarCupoSeccion(int $secId): bool { return true; }
            public function verificarCruceHorarios(array $secIds): array { return []; }
            public function listarPorEstudiante(int $eId): array { return []; }
            public function listarPorPeriodo(int $pId, int $l = 50, int $o = 0): array { return []; }
        };

        $useCase = new RegistrarMatricula($matriculaRepo, $estudianteRepo, $periodoRepo, $cursoRepo);
        $dto = new RegistrarMatriculaDTO(1, 1, [1]);
        $matricula = $useCase->ejecutar($dto);

        assert($matricula->getId() === 99);
        assert($matricula->getTotalCreditos() === 4);
        assert(count($matricula->getDetalles()) === 1);

        echo "OK\n";
    }
}

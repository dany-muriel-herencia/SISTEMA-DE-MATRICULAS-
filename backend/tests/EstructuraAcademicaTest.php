<?php

declare(strict_types=1);

namespace Tests;

require_once __DIR__ . '/../vendor/autoload.php';

use App\Application\CasoDeUso\GestionAcademica\GestionarEstructuraAcademica;
use App\Dominio\Entidades\Carrera;
use App\Dominio\Entidades\Escuela;
use App\Dominio\Entidades\Facultad;
use App\Dominio\Repositorios\CarreraRepositorio;
use App\Dominio\Repositorios\EscuelaRepositorio;
use App\Dominio\Repositorios\FacultadRepositorio;
use DomainException;
use InvalidArgumentException;

class EstructuraAcademicaTest
{
    public static function runAll(): void
    {
        echo "========================================\n";
        echo " TEST: GestionarEstructuraAcademica     \n";
        echo "========================================\n\n";

        self::testRegistrarFacultad();
        self::testRegistrarEscuela();
        self::testRegistrarCarrera();
        self::testListarEstructura();

        echo "\n[PASS] Todos los tests de Estructura Académica pasaron exitosamente!\n";
    }

    private static function testRegistrarFacultad(): void
    {
        echo "[TEST] Registrar Facultad... ";

        $facultadRepo = new class implements FacultadRepositorio {
            public array $guardados = [];
            public function buscarPorId(int $id): ?Facultad { return null; }
            public function listar(): array { return []; }
            public function guardar(Facultad $f): void { $this->guardados[] = $f; }
            public function actualizar(Facultad $f): void {}
        };
        $escuelaRepo = new class implements EscuelaRepositorio {
            public function buscarPorId(int $id): ?Escuela { return null; }
            public function listar(): array { return []; }
            public function guardar(Escuela $e): void {}
            public function actualizar(Escuela $e): void {}
        };
        $carreraRepo = new class implements CarreraRepositorio {
            public function buscarPorId(int $id): ?Carrera { return null; }
            public function buscarPorCodigo(string $c): ?Carrera { return null; }
            public function listar(): array { return []; }
            public function guardar(Carrera $c): void {}
            public function actualizar(Carrera $c): void {}
        };

        $useCase = new GestionarEstructuraAcademica($facultadRepo, $escuelaRepo, $carreraRepo);

        $res = $useCase->registrarFacultad('Facultad de Ingeniería', 'FIAG', 'Dr. Decano');
        assert($res['nombre'] === 'Facultad de Ingeniería');
        assert($res['descripcion'] === 'FIAG');
        assert($res['decano'] === 'Dr. Decano');
        assert(count($facultadRepo->guardados) === 1);

        // Test validación nombre obligatorio
        try {
            $useCase->registrarFacultad('   ');
            assert(false, 'Debería lanzar InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            assert($e->getMessage() === 'El nombre de la facultad es obligatorio.');
        }

        echo "OK\n";
    }

    private static function testRegistrarEscuela(): void
    {
        echo "[TEST] Registrar Escuela (Caso Principal)... ";

        $facultadRepo = new class implements FacultadRepositorio {
            public function buscarPorId(int $id): ?Facultad {
                if ($id === 1) {
                    return new Facultad(1, 'Facultad de Ingeniería', 'FIAG', 'Dr. Decano');
                }
                return null;
            }
            public function listar(): array { return []; }
            public function guardar(Facultad $f): void {}
            public function actualizar(Facultad $f): void {}
        };

        $escuelaRepo = new class implements EscuelaRepositorio {
            public array $guardados = [];
            public function buscarPorId(int $id): ?Escuela { return null; }
            public function listar(): array { return []; }
            public function guardar(Escuela $e): void { $this->guardados[] = $e; }
            public function actualizar(Escuela $e): void {}
        };

        $carreraRepo = new class implements CarreraRepositorio {
            public function buscarPorId(int $id): ?Carrera { return null; }
            public function buscarPorCodigo(string $c): ?Carrera { return null; }
            public function listar(): array { return []; }
            public function guardar(Carrera $c): void {}
            public function actualizar(Carrera $c): void {}
        };

        $useCase = new GestionarEstructuraAcademica($facultadRepo, $escuelaRepo, $carreraRepo);

        // Registro exitoso
        $res = $useCase->registrarEscuela(1, 'Escuela de Ingeniería de Sistemas', 'ESIS', 'Ing. Director');
        assert($res['id_facultad'] === 1);
        assert($res['nombre'] === 'Escuela de Ingeniería de Sistemas');
        assert($res['descripcion'] === 'ESIS');
        assert($res['director'] === 'Ing. Director');
        assert(count($escuelaRepo->guardados) === 1);

        /** @var Escuela $escuelaGuardada */
        $escuelaGuardada = $escuelaRepo->guardados[0];
        assert($escuelaGuardada->getIdEscuela() === 0);
        assert($escuelaGuardada->getIdFacultad() === 1);
        assert($escuelaGuardada->getNombre() === 'Escuela de Ingeniería de Sistemas');

        // Test facultad inexistente -> DomainException
        try {
            $useCase->registrarEscuela(999, 'Escuela X');
            assert(false, 'Debería lanzar DomainException');
        } catch (DomainException $e) {
            assert(str_contains($e->getMessage(), 'Facultad con ID 999 no encontrada'));
        }

        // Test nombre obligatorio -> InvalidArgumentException
        try {
            $useCase->registrarEscuela(1, '   ');
            assert(false, 'Debería lanzar InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            assert($e->getMessage() === 'El nombre de la escuela profesional es obligatorio.');
        }

        echo "OK\n";
    }

    private static function testRegistrarCarrera(): void
    {
        echo "[TEST] Registrar Carrera... ";

        $facultadRepo = new class implements FacultadRepositorio {
            public function buscarPorId(int $id): ?Facultad { return null; }
            public function listar(): array { return []; }
            public function guardar(Facultad $f): void {}
            public function actualizar(Facultad $f): void {}
        };

        $escuelaRepo = new class implements EscuelaRepositorio {
            public function buscarPorId(int $id): ?Escuela {
                if ($id === 10) {
                    return new Escuela(10, 1, 'Escuela de Sistemas', 'ESIS', 'Director');
                }
                return null;
            }
            public function listar(): array { return []; }
            public function guardar(Escuela $e): void {}
            public function actualizar(Escuela $e): void {}
        };

        $carreraRepo = new class implements CarreraRepositorio {
            public array $guardados = [];
            public function buscarPorId(int $id): ?Carrera { return null; }
            public function buscarPorCodigo(string $c): ?Carrera { return null; }
            public function listar(): array { return []; }
            public function guardar(Carrera $c): void { $this->guardados[] = $c; }
            public function actualizar(Carrera $c): void {}
        };

        $useCase = new GestionarEstructuraAcademica($facultadRepo, $escuelaRepo, $carreraRepo);

        $res = $useCase->registrarCarrera(10, 'Ingeniería de Sistemas', 'IS-01', 5, true);
        assert($res['id_escuela'] === 10);
        assert($res['nombre'] === 'Ingeniería de Sistemas');
        assert($res['codigo'] === 'IS-01');
        assert(count($carreraRepo->guardados) === 1);

        echo "OK\n";
    }

    private static function testListarEstructura(): void
    {
        echo "[TEST] Listar Estructura... ";

        $facultadRepo = new class implements FacultadRepositorio {
            public function buscarPorId(int $id): ?Facultad { return null; }
            public function listar(): array {
                return [new Facultad(1, 'Facultad de Ciencias', 'FC', 'Decano FC')];
            }
            public function guardar(Facultad $f): void {}
            public function actualizar(Facultad $f): void {}
        };

        $escuelaRepo = new class implements EscuelaRepositorio {
            public function buscarPorId(int $id): ?Escuela { return null; }
            public function listar(): array {
                return [new Escuela(10, 1, 'Escuela de Física', 'EF', 'Director EF')];
            }
            public function guardar(Escuela $e): void {}
            public function actualizar(Escuela $e): void {}
        };

        $carreraRepo = new class implements CarreraRepositorio {
            public function buscarPorId(int $id): ?Carrera { return null; }
            public function buscarPorCodigo(string $c): ?Carrera { return null; }
            public function listar(): array {
                return [new Carrera(100, 10, 'Lic. Física', 'FIS-01', 5, true)];
            }
            public function guardar(Carrera $c): void {}
            public function actualizar(Carrera $c): void {}
        };

        $useCase = new GestionarEstructuraAcademica($facultadRepo, $escuelaRepo, $carreraRepo);
        $res = $useCase->listarEstructura();

        assert(count($res['facultades']) === 1);
        assert($res['facultades'][0]['nombre'] === 'Facultad de Ciencias');
        assert($res['facultades'][0]['decano'] === 'Decano FC');

        assert(count($res['escuelas']) === 1);
        assert($res['escuelas'][0]['nombre'] === 'Escuela de Física');
        assert($res['escuelas'][0]['director'] === 'Director EF');

        assert(count($res['carreras']) === 1);
        assert($res['carreras'][0]['nombre'] === 'Lic. Física');
        assert($res['carreras'][0]['codigo'] === 'FIS-01');

        echo "OK\n";
    }
}

EstructuraAcademicaTest::runAll();

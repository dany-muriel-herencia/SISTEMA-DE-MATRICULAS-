<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Dominio\Entidades\Usuario;
use App\Dominio\Entidades\Estudiante;
use App\Dominio\Entidades\Docente;
use App\Dominio\Entidades\Administrador;
use App\Dominio\Entidades\Sesion;
use App\Dominio\Entidades\Auditoria;
use App\Dominio\Entidades\Facultad;
use App\Dominio\Entidades\Escuela;
use App\Dominio\Entidades\Carrera;
use App\Dominio\Entidades\PlanEstudio;
use App\Dominio\Entidades\Curso;
use App\Dominio\Entidades\Curriculum;
use App\Dominio\Entidades\Prerequisito;
use App\Dominio\Entidades\PeriodoAcademico;
use App\Dominio\Entidades\Seccion;
use App\Dominio\Entidades\Horario;
use App\Dominio\Entidades\Aula;
use App\Dominio\Entidades\Matricula;
use App\Dominio\Entidades\DetalleMatricula;
use App\Dominio\Entidades\Pago;
use App\Dominio\Entidades\ConceptoPago;
use App\Dominio\Entidades\ComprobantePago;

use App\Dominio\Repositorios\UsuarioRepositorio;
use App\Dominio\Repositorios\EstudianteRepositorio;
use App\Dominio\Repositorios\DocenteRepositorio;
use App\Dominio\Repositorios\SesionRepositorio;
use App\Dominio\Repositorios\AuditoriaRepositorio;
use App\Dominio\Repositorios\FacultadRepositorio;
use App\Dominio\Repositorios\EscuelaRepositorio;
use App\Dominio\Repositorios\CarreraRepositorio;
use App\Dominio\Repositorios\PlanEstudioRepositorio;
use App\Dominio\Repositorios\CursoRepositorio;
use App\Dominio\Repositorios\CurriculumRepositorio;
use App\Dominio\Repositorios\PrerequisitoRepositorio;
use App\Dominio\Repositorios\PeriodoAcademicoRepositorio;
use App\Dominio\Repositorios\SeccionRepositorio;
use App\Dominio\Repositorios\HorarioRepositorio;
use App\Dominio\Repositorios\AulaRepositorio;
use App\Dominio\Repositorios\MatriculaRepositorio;
use App\Dominio\Repositorios\DetalleMatriculaRepositorio;
use App\Dominio\Repositorios\PagoRepositorio;
use App\Dominio\Repositorios\ConceptoPagoRepositorio;
use App\Dominio\Repositorios\ComprobantePagoRepositorio;

use App\Presentacion\Controladores\UsuarioControlador;
use App\Presentacion\Controladores\EstudianteControlador;
use App\Presentacion\Controladores\DocenteControlador;
use App\Presentacion\Controladores\AdministradorControlador;
use App\Presentacion\Controladores\SesionControlador;
use App\Presentacion\Controladores\AuditoriaControlador;
use App\Presentacion\Controladores\FacultadControlador;
use App\Presentacion\Controladores\EscuelaControlador;
use App\Presentacion\Controladores\CarreraControlador;
use App\Presentacion\Controladores\PlanEstudioControlador;
use App\Presentacion\Controladores\CursoControlador;
use App\Presentacion\Controladores\CurriculumControlador;
use App\Presentacion\Controladores\PrerequisitoControlador;
use App\Presentacion\Controladores\PeriodoAcademicoControlador;
use App\Presentacion\Controladores\SeccionControlador;
use App\Presentacion\Controladores\HorarioControlador;
use App\Presentacion\Controladores\AulaControlador;
use App\Presentacion\Controladores\MatriculaControlador;
use App\Presentacion\Controladores\DetalleMatriculaControlador;
use App\Presentacion\Controladores\PagoControlador;
use App\Presentacion\Controladores\ConceptoPagoControlador;
use App\Presentacion\Controladores\ComprobantePagoControlador;

$passed = 0;
$failed = 0;

function assertTest(string $name, bool $condition, string $details = ''): void {
    global $passed, $failed;
    if ($condition) {
        echo " [PASS] $name\n";
        $passed++;
    } else {
        echo " [FAIL] $name: $details\n";
        $failed++;
    }
}

echo "=== INICIANDO PRUEBAS UNITARIAS DE CONTROLADORES ===\n\n";

// 1. UsuarioControlador
$usuarioRepo = new class implements UsuarioRepositorio {
    public array $db = [];
    public function buscarPorId(int $idUsuario): ?Usuario {
        return $this->db[$idUsuario] ?? null;
    }
    public function buscarPorEmail(string $email): ?Usuario {
        foreach ($this->db as $u) {
            if ($u->getEmail() === $email) return $u;
        }
        return null;
    }
    public function guardar(Usuario $usuario): void {
        $this->db[1] = $usuario;
    }
    public function actualizar(Usuario $usuario): void {
        $this->db[$usuario->getIdUsuario()] = $usuario;
    }
    public function eliminar(int $idUsuario): void {
        unset($this->db[$idUsuario]);
    }
    public function listar(int $limit = 50, int $offset = 0): array {
        return array_values($this->db);
    }
};

$userCtrl = new UsuarioControlador($usuarioRepo);
$resReg = $userCtrl->registrar([
    'nombre' => 'Juan Perez',
    'email' => 'juan@uni.edu.pe',
    'contrasenha' => 'secret123',
    'rol' => 'ESTUDIANTE'
]);
assertTest('UsuarioControlador::registrar', $resReg['success'] === true && $resReg['data']['email'] === 'juan@uni.edu.pe');

$resList = $userCtrl->listar();
assertTest('UsuarioControlador::listar', $resList['success'] === true && count($resList['data']) === 1);

// 2. EstudianteControlador
$estudianteRepo = new class implements EstudianteRepositorio {
    public ?Estudiante $item = null;
    public function buscarPorId(int $id): ?Estudiante { return $this->item; }
    public function buscarPorCodigo(string $codigo): ?Estudiante { return $this->item; }
    public function buscarPorDni(string $dni): ?Estudiante { return $this->item; }
    public function guardar(Estudiante $e): void { $this->item = $e; }
    public function actualizar(Estudiante $e): void { $this->item = $e; }
};
$estCtrl = new EstudianteControlador($estudianteRepo);
$resEst = $estCtrl->registrar([
    'nombre' => 'Ana Gomez',
    'email' => 'ana@uni.edu.pe',
    'contrasenha' => 'pass123',
    'codigo_universitario' => '20230001',
    'dni' => '78945612'
]);
assertTest('EstudianteControlador::registrar', $resEst['success'] === true && $resEst['data']['codigo_universitario'] === '20230001');

// 3. DocenteControlador
$docenteRepo = new class implements DocenteRepositorio {
    public ?Docente $item = null;
    public function buscarPorId(int $id): ?Docente { return $this->item; }
    public function buscarPorCodigo(string $codigo): ?Docente { return $this->item; }
    public function guardar(Docente $d): void { $this->item = $d; }
    public function actualizar(Docente $d): void { $this->item = $d; }
};
$docCtrl = new DocenteControlador($docenteRepo);
$resDoc = $docCtrl->registrar([
    'id_usuario' => 2,
    'nombre' => 'Dr. Carlos',
    'email' => 'carlos@uni.edu.pe',
    'contrasenha' => 'doc123',
    'codigo' => 'DOC001',
    'especialidad' => 'Sistemas',
    'grado_academico' => 'Doctor'
]);
assertTest('DocenteControlador::registrar', $resDoc['success'] === true && $resDoc['data']['codigo'] === 'DOC001');

// 4. AdministradorControlador
$adminCtrl = new AdministradorControlador($usuarioRepo);
$resAdm = $adminCtrl->registrar([
    'nombre' => 'Admin Root',
    'email' => 'admin@uni.edu.pe',
    'contrasenha' => 'root123',
    'nivel' => 'SUPERADMIN'
]);
assertTest('AdministradorControlador::registrar', $resAdm['success'] === true && $resAdm['data']['nivel'] === 'SUPERADMIN');

// 5. SesionControlador
$sesionRepo = new class implements SesionRepositorio {
    public ?Sesion $item = null;
    public function buscarPorId(int $id): ?Sesion { return $this->item; }
    public function buscarPorToken(string $token): ?Sesion { return $this->item; }
    public function listarPorUsuario(int $idUsuario): array { return $this->item ? [$this->item] : []; }
    public function guardar(Sesion $s): void { $this->item = $s; }
    public function actualizar(Sesion $s): void { $this->item = $s; }
    public function eliminar(int $id): void { $this->item = null; }
};
$sesCtrl = new SesionControlador($sesionRepo);
$resSes = $sesCtrl->iniciarSesion(['id_usuario' => 1]);
assertTest('SesionControlador::iniciarSesion', $resSes['success'] === true && !empty($resSes['data']['token']));

// 6. AuditoriaControlador
$auditoriaRepo = new class implements AuditoriaRepositorio {
    public array $list = [];
    public function buscarPorId(int $id): ?Auditoria { return $this->list[0] ?? null; }
    public function listar(): array { return $this->list; }
    public function listarPorUsuario(int $idUsuario): array { return $this->list; }
    public function guardar(Auditoria $a): void { $this->list[] = $a; }
};
$audCtrl = new AuditoriaControlador($auditoriaRepo);
$resAud = $audCtrl->registrar([
    'id_usuario' => 1,
    'accion' => 'LOGIN',
    'tabla_afectada' => 'usuario'
]);
assertTest('AuditoriaControlador::registrar', $resAud['success'] === true && $resAud['data']['accion'] === 'LOGIN');

// 7. FacultadControlador
$facultadRepo = new class implements FacultadRepositorio {
    public ?Facultad $item = null;
    public function buscarPorId(int $id): ?Facultad { return $this->item; }
    public function listar(): array { return $this->item ? [$this->item] : []; }
    public function guardar(Facultad $f): void { $this->item = $f; }
    public function actualizar(Facultad $f): void { $this->item = $f; }
};
$facCtrl = new FacultadControlador($facultadRepo);
$resFac = $facCtrl->registrar(['nombre' => 'Facultad de Ingeniería', 'decano' => 'Ing. Torres']);
assertTest('FacultadControlador::registrar', $resFac['success'] === true && $resFac['data']['nombre'] === 'Facultad de Ingeniería');

// 8. EscuelaControlador
$escuelaRepo = new class implements EscuelaRepositorio {
    public ?Escuela $item = null;
    public function buscarPorId(int $id): ?Escuela { return $this->item; }
    public function listar(): array { return $this->item ? [$this->item] : []; }
    public function guardar(Escuela $e): void { $this->item = $e; }
    public function actualizar(Escuela $e): void { $this->item = $e; }
};
$escCtrl = new EscuelaControlador($escuelaRepo);
$resEsc = $escCtrl->registrar(['id_facultad' => 1, 'nombre' => 'Escuela de Software', 'director' => 'Lic. Ramos']);
assertTest('EscuelaControlador::registrar', $resEsc['success'] === true && $resEsc['data']['nombre'] === 'Escuela de Software');

// 9. CarreraControlador
$carreraRepo = new class implements CarreraRepositorio {
    public ?Carrera $item = null;
    public function buscarPorId(int $id): ?Carrera { return $this->item; }
    public function buscarPorCodigo(string $codigo): ?Carrera { return $this->item; }
    public function listar(): array { return $this->item ? [$this->item] : []; }
    public function guardar(Carrera $c): void { $this->item = $c; }
    public function actualizar(Carrera $c): void { $this->item = $c; }
};
$carCtrl = new CarreraControlador($carreraRepo);
$resCar = $carCtrl->registrar(['id_escuela' => 1, 'nombre' => 'Ingeniería de Software', 'codigo' => 'ISW', 'duracion' => 10]);
assertTest('CarreraControlador::registrar', $resCar['success'] === true && $resCar['data']['codigo'] === 'ISW');

// 10. PlanEstudioControlador
$planRepo = new class implements PlanEstudioRepositorio {
    public ?PlanEstudio $item = null;
    public function buscarPorId(int $id): ?PlanEstudio { return $this->item; }
    public function listar(): array { return $this->item ? [$this->item] : []; }
    public function guardar(PlanEstudio $p): void { $this->item = $p; }
    public function actualizar(PlanEstudio $p): void { $this->item = $p; }
};
$planCtrl = new PlanEstudioControlador($planRepo);
$resPlan = $planCtrl->registrar(['id_carrera' => 1, 'nombre' => 'Plan 2024', 'fecha_inicio' => '2024-01-01']);
assertTest('PlanEstudioControlador::registrar', $resPlan['success'] === true && $resPlan['data']['nombre'] === 'Plan 2024');

// 11. CursoControlador
$cursoRepo = new class implements CursoRepositorio {
    public ?Curso $item = null;
    public function buscarPorId(int $id): ?Curso { return $this->item; }
    public function buscarPorCodigo(string $c): ?Curso { return $this->item; }
    public function listarActivos(): array { return $this->item ? [$this->item] : []; }
    public function guardar(Curso $c): void { $this->item = $c; }
    public function actualizar(Curso $c): void { $this->item = $c; }
};
$curCtrl = new CursoControlador($cursoRepo);
$resCur = $curCtrl->registrar(['nombre' => 'Base de Datos I', 'codigo' => 'BD101', 'creditos' => 4, 'horas_teoria' => 2, 'horas_practica' => 4, 'ciclo' => 'IV']);
assertTest('CursoControlador::registrar', $resCur['success'] === true && $resCur['data']['codigo'] === 'BD101');

// 12. CurriculumControlador
$curriculumRepo = new class implements CurriculumRepositorio {
    public ?Curriculum $item = null;
    public function buscarPorId(int $id): ?Curriculum { return $this->item; }
    public function listar(): array { return $this->item ? [$this->item] : []; }
    public function guardar(Curriculum $c): void { $this->item = $c; }
    public function actualizar(Curriculum $c): void { $this->item = $c; }
};
$curriCtrl = new CurriculumControlador($curriculumRepo);
$resCurri = $curriCtrl->registrar(['id_plan' => 1, 'id_curso' => 1, 'ciclo' => 4, 'obligatorio' => true]);
assertTest('CurriculumControlador::registrar', $resCurri['success'] === true && $resCurri['data']['id_plan'] === 1);

// 13. PrerequisitoControlador
$prereqRepo = new class implements PrerequisitoRepositorio {
    public ?Prerequisito $item = null;
    public function buscarPorId(int $id): ?Prerequisito { return $this->item; }
    public function listar(): array { return $this->item ? [$this->item] : []; }
    public function guardar(Prerequisito $p): void { $this->item = $p; }
    public function eliminar(int $id): void { $this->item = null; }
};
$preCtrl = new PrerequisitoControlador($prereqRepo);
$resPre = $preCtrl->registrar(['id_curso' => 2, 'id_curso_requerido' => 1]);
assertTest('PrerequisitoControlador::registrar', $resPre['success'] === true && $resPre['data']['id_curso'] === 2);

// 14. PeriodoAcademicoControlador
$periodoRepo = new class implements PeriodoAcademicoRepositorio {
    public ?PeriodoAcademico $item = null;
    public function buscarPorId(int $id): ?PeriodoAcademico { return $this->item; }
    public function obtenerPeriodoActivo(): ?PeriodoAcademico { return $this->item; }
    public function guardar(PeriodoAcademico $p): void { $this->item = $p; }
    public function actualizar(PeriodoAcademico $p): void { $this->item = $p; }
    public function listar(): array { return $this->item ? [$this->item] : []; }
};
$perCtrl = new PeriodoAcademicoControlador($periodoRepo);
$resPer = $perCtrl->registrar(['nombre' => '2026-I', 'fecha_inicio' => '2026-03-01', 'fecha_fin' => '2026-07-31']);
assertTest('PeriodoAcademicoControlador::registrar', $resPer['success'] === true && $resPer['data']['nombre'] === '2026-I');

// 15. SeccionControlador
$seccionRepo = new class implements SeccionRepositorio {
    public ?Seccion $item = null;
    public function buscarPorId(int $id): ?Seccion { return $this->item; }
    public function listarDisponibles(): array { return $this->item ? [$this->item] : []; }
    public function guardar(Seccion $s): void { $this->item = $s; }
    public function actualizar(Seccion $s): void { $this->item = $s; }
};
$secCtrl = new SeccionControlador($seccionRepo);
$resSec = $secCtrl->registrar(['id_curso' => 1, 'id_periodo' => 1, 'id_docente' => 2, 'codigo' => 'SEC-A', 'vacantes' => 30]);
assertTest('SeccionControlador::registrar', $resSec['success'] === true && $resSec['data']['codigo'] === 'SEC-A');

// 16. HorarioControlador
$horarioRepo = new class implements HorarioRepositorio {
    public ?Horario $item = null;
    public function buscarPorId(int $id): ?Horario { return $this->item; }
    public function listar(): array { return $this->item ? [$this->item] : []; }
    public function guardar(Horario $h): void { $this->item = $h; }
    public function actualizar(Horario $h): void { $this->item = $h; }
    public function eliminar(int $id): void { $this->item = null; }
    public function existeConflictoAula(int $idAula, string $dia, string $ini, string $fin): bool { return false; }
    public function existeConflictoDocente(int $idDoc, string $dia, string $ini, string $fin): bool { return false; }
};
$horCtrl = new HorarioControlador($horarioRepo);
$resHor = $horCtrl->registrar(['id_seccion' => 1, 'id_aula' => 1, 'dia_semana' => 'LUNES', 'hora_inicio' => '08:00', 'hora_fin' => '10:00', 'modalidad' => 'PRESENCIAL']);
assertTest('HorarioControlador::registrar', $resHor['success'] === true && $resHor['data']['dia_semana'] === 'LUNES');

// 17. AulaControlador
$aulaRepo = new class implements AulaRepositorio {
    public ?Aula $item = null;
    public function buscarPorId(int $id): ?Aula { return $this->item; }
    public function listar(): array { return $this->item ? [$this->item] : []; }
    public function listarDisponibles(): array { return $this->item ? [$this->item] : []; }
    public function guardar(Aula $a): void { $this->item = $a; }
    public function actualizar(Aula $a): void { $this->item = $a; }
};
$aulCtrl = new AulaControlador($aulaRepo);
$resAul = $aulCtrl->registrar(['nombre' => 'Lab 101', 'ubicacion' => 'Pabellon A', 'capacidad' => 35, 'tipo' => 'LABORATORIO']);
assertTest('AulaControlador::registrar', $resAul['success'] === true && $resAul['data']['nombre'] === 'Lab 101');

// 18. MatriculaControlador
$matriculaRepo = new class implements MatriculaRepositorio {
    public ?Matricula $item = null;
    public function buscarPorId(int $id): ?Matricula { return $this->item; }
    public function buscarPorEstudiante(int $id): array { return $this->item ? [$this->item] : []; }
    public function buscarPorPeriodo(int $id): array { return $this->item ? [$this->item] : []; }
    public function buscarPorCodigo(string $c): ?Matricula { return $this->item; }
    public function listarPorEstudiante(int $id): array { return $this->item ? [$this->item] : []; }
    public function guardar(Matricula $m): void { $this->item = $m; }
    public function actualizar(Matricula $m): void { $this->item = $m; }
    public function registrarConDetalles(Matricula $m, array $d): int { return 1; }
};
$matCtrl = new MatriculaControlador($matriculaRepo);
$resMat = $matCtrl->registrar(['id_estudiante' => 1, 'id_periodo' => 1, 'total_creditos' => 20, 'codigo_matricula' => 'MAT2026-0001']);
assertTest('MatriculaControlador::registrar', $resMat['success'] === true && $resMat['data']['codigo_matricula'] === 'MAT2026-0001');

// 19. DetalleMatriculaControlador
$detalleRepo = new class implements DetalleMatriculaRepositorio {
    public ?DetalleMatricula $item = null;
    public function buscarPorId(int $id): ?DetalleMatricula { return $this->item; }
    public function listarPorMatricula(int $id): array { return $this->item ? [$this->item] : []; }
    public function guardar(DetalleMatricula $d): void { $this->item = $d; }
    public function actualizar(DetalleMatricula $d): void { $this->item = $d; }
    public function eliminar(int $id): void { $this->item = null; }
};
$detCtrl = new DetalleMatriculaControlador($detalleRepo);
$resDet = $detCtrl->registrar(['id_matricula' => 1, 'id_seccion' => 1, 'estado' => 'MATRICULADO']);
assertTest('DetalleMatriculaControlador::registrar', $resDet['success'] === true && $resDet['data']['id_seccion'] === 1);

// 20. PagoControlador
$pagoRepo = new class implements PagoRepositorio {
    public ?Pago $item = null;
    public function buscarPorId(int $id): ?Pago { return $this->item; }
    public function buscarPorEstudiante(int $id): array { return $this->item ? [$this->item] : []; }
    public function guardar(Pago $p): void { $this->item = $p; }
    public function actualizar(Pago $p): void { $this->item = $p; }
};
$pagCtrl = new PagoControlador($pagoRepo);
$resPag = $pagCtrl->registrar(['id_estudiante' => 1, 'id_concepto' => 1, 'monto' => 350.00, 'metodo_pago' => 'TARJETA']);
assertTest('PagoControlador::registrar', $resPag['success'] === true && $resPag['data']['monto'] === 350.0);

// 21. ConceptoPagoControlador
$conceptoRepo = new class implements ConceptoPagoRepositorio {
    public ?ConceptoPago $item = null;
    public function buscarPorId(int $id): ?ConceptoPago { return $this->item; }
    public function listar(): array { return $this->item ? [$this->item] : []; }
    public function guardar(ConceptoPago $c): void { $this->item = $c; }
    public function actualizar(ConceptoPago $c): void { $this->item = $c; }
};
$conCtrl = new ConceptoPagoControlador($conceptoRepo);
$resCon = $conCtrl->registrar(['nombre' => 'Matrícula Regular', 'descripcion' => 'Derecho de matrícula', 'monto' => 350.00]);
assertTest('ConceptoPagoControlador::registrar', $resCon['success'] === true && $resCon['data']['monto'] === 350.0);

// 22. ComprobantePagoControlador
$comprobanteRepo = new class implements ComprobantePagoRepositorio {
    public ?ComprobantePago $item = null;
    public function buscarPorId(int $id): ?ComprobantePago { return $this->item; }
    public function buscarPorPago(int $id): ?ComprobantePago { return $this->item; }
    public function guardar(ComprobantePago $c): void { $this->item = $c; }
    public function actualizar(ComprobantePago $c): void { $this->item = $c; }
};
$comCtrl = new ComprobantePagoControlador($comprobanteRepo);
$resCom = $comCtrl->emitir(['id_pago' => 1, 'tipo' => 'BOLETA', 'serie' => 'B001', 'numero' => '00000123']);
assertTest('ComprobantePagoControlador::emitir', $resCom['success'] === true && $resCom['data']['serie_numero'] === 'B001-00000123');

echo "\n============================================\n";
echo "RESULTADO: $passed pruebas superadas, $failed fallidas.\n";
if ($failed === 0) {
    echo "¡TODOS LOS CONTROLADORES FUNCIONAN CORRECTAMENTE!\n";
}
echo "============================================\n";

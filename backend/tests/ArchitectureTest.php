<?php
declare(strict_types=1);
namespace Tests;
use App\Infrastructure\Repositories as R;
use App\Dominio\Entidades as E;
use App\Application\CasoDeUso\Auth\IniciarSesion;
use App\Application\CasoDeUso\Auth\CerrarSesion;
use App\Presentation\Middleware\AuthMiddleware;
use App\Application\CasoDeUso\Matricula\RegistrarMatricula;
use App\Application\DTO\RegistrarMatriculaDTO;
final class ArchitectureTest {
    private static int $checks=0;
    private static function check(bool $ok,string $message): void {
        if(!$ok) throw new \RuntimeException($message);
        self::$checks++;
    }
    private static function rejects(callable $fn,string $message): void {
        try { $fn(); } catch(\DomainException|\InvalidArgumentException $e) { self::$checks++; return; }
        throw new \RuntimeException($message);
    }
    public static function runAll(): void {
        foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(dirname(__DIR__).'/src')) as $f) {
            if($f->getExtension()!=='php')continue;
            $s=file_get_contents($f->getPathname());
            if(preg_match('/^namespace\s+([^;]+);/m',$s,$ns) && preg_match('/^(?:final |abstract )?(class|interface)\s+(\w+)/m',$s,$cl)) {
                $name=$ns[1].'\\'.$cl[2];
                self::check(class_exists($name)||interface_exists($name),'Autoload: '.$name);
            }
        }
        $db=new \PDO('sqlite::memory:',null,null,[\PDO::ATTR_ERRMODE=>\PDO::ERRMODE_EXCEPTION,\PDO::ATTR_DEFAULT_FETCH_MODE=>\PDO::FETCH_ASSOC]);
        $db->exec('PRAGMA foreign_keys=ON');
        // Exercise the checked-in schema in a disposable database; MySQL locking is tested separately.
        $sql=file_get_contents(dirname(__DIR__,2).'/database/schema/01_initial_schema.sql');
        $sql=preg_replace('/CREATE DATABASE.*?;/s','',$sql);
        $sql=preg_replace('/^USE .*?;/m','',$sql);
        $sql=str_replace('INT AUTO_INCREMENT PRIMARY KEY','INTEGER PRIMARY KEY AUTOINCREMENT',$sql);
        $db->exec($sql);
        $usuarios=new R\MySQLUsuarioRepositorio($db);
        $u=new E\Usuario(0,'Alumno','alumno@example.test',password_hash('clave-prueba',PASSWORD_DEFAULT),'ESTUDIANTE',true,new \DateTimeImmutable());
        $usuarios->guardar($u);
        self::check($u->getIdUsuario()>0,'ID de usuario generado');
        self::check(!isset($usuarios->buscarPorId($u->getIdUsuario())->toArray()['contrasenha']),'No exponer contraseña');
        $sesiones=new R\MySQLSesionRepositorio($db);
        $inicio=new IniciarSesion($usuarios,$sesiones);
        $s=$inicio->ejecutar($u->getEmail(),'clave-prueba','127.0.0.1');
        $auth=new AuthMiddleware($sesiones,$usuarios);
        self::check($auth->autenticar($s->getToken())?->getIdUsuario()===$u->getIdUsuario(),'Autenticación persistida');
        self::check($auth->autenticar(str_repeat('f',64))===null,'Token inventado rechazado');
        (new CerrarSesion($sesiones))->ejecutar($s->getToken());
        self::check($auth->autenticar($s->getToken())===null,'Token revocado');
        $expired=new E\Sesion(0,$u->getIdUsuario(),str_repeat('a',64),new \DateTimeImmutable('-2 days'),null,null,true);
        $sesiones->guardar($expired);
        self::check($auth->autenticar($expired->getToken())===null,'Token expirado');
        $e=new E\Estudiante($u->getIdUsuario(),$u->getNombre(),$u->getEmail(),$u->getContrasenha(),$u->getRol(),true,$u->getFechaCreacion(),'2026-001','12345678',new \DateTimeImmutable('2000-01-01'),new \DateTimeImmutable('2026-01-01'),0);
        $estudiantes=new R\MySQLEstudianteRepositorio($db); $estudiantes->guardar($e);
        self::check(count($estudiantes->listar())===1,'Perfil de estudiante sin duplicar usuario');
        $doc=new E\Usuario(0,'Docente','doc@example.test','hash','DOCENTE',true,new \DateTimeImmutable()); $usuarios->guardar($doc);
        $db->exec("INSERT INTO docente VALUES ({$doc->getIdUsuario()},'D01','Sistemas','Doctor')");
        self::check((new R\MySQLDocenteRepositorio($db))->buscarPorId($doc->getIdUsuario())->getNombre()==='Docente','Herencia de docente');
        $db->exec("INSERT INTO periodo_academico VALUES(1,'Periodo', '2026-01-01','2035-12-31','2020-01-01','2035-12-31','MATRICULA_ABIERTA')");
        $periodos=new R\MySQLPeriodoAcademicoRepositorio($db);
        self::check($periodos->obtenerPeriodoActivo()->getEstado()==='MATRICULA_ABIERTA','Estado textual del periodo');
        $cursos=new R\MySQLCursoRepositorio($db);
        foreach([['C1','I'],['C2','II']] as [$code,$ciclo]) {
            $c=new E\Curso(0,$code,$code,4,2,2,$ciclo,true); $cursos->guardar($c);
            self::check($c->getIdCurso()>0 && $cursos->buscarPorId($c->getIdCurso())->getCiclo()===$ciclo,'Ciclo textual del curso');
        }
        $db->exec("INSERT INTO seccion VALUES(1,1,1,{$doc->getIdUsuario()},'C1-A',10,10),(2,2,1,{$doc->getIdUsuario()},'C2-A',10,10)");
        $db->exec("INSERT INTO aula VALUES(1,'Aula',NULL,40,NULL,1,1)");
        self::check((new R\MySQLAulaRepositorio($db))->buscarPorId(1)->getUbicacion()===null,'Aula nullable');
        $db->exec("INSERT INTO horario VALUES(1,1,1,'LUNES','08:00:00','10:00:00','PRESENCIAL'),(2,2,1,'LUNES','09:00:00','11:00:00','PRESENCIAL')");
        $hr=new R\MySQLHorarioRepositorio($db); $h=$hr->buscarPorId(1); $hr->actualizar($h);
        self::check($h->getIdAula()===1 && $h->getHoraInicio()->format('H:i:s')==='08:00:00','Horario completo');
        $ar=new R\MySQLAuditoriaRepositorio($db);
        $ar->guardar(new E\Auditoria(1,$u->getIdUsuario(),'TEST','usuario',new \DateTimeImmutable(),null,null,null));
        self::check($ar->buscarPorId(1)->getIdUsuario()===$u->getIdUsuario(),'Usuario en auditoría');
        $mr=new R\MySQLMatriculaRepositorio($db);
        $registrar=new RegistrarMatricula($mr,$estudiantes,$periodos,$cursos,new R\MySQLSeccionRepositorio($db));
        self::rejects(fn()=>$registrar->ejecutar(new RegistrarMatriculaDTO($u->getIdUsuario(),1,[1,2])),'Cruce debe rechazarse');
        self::check((int)$db->query('SELECT COUNT(*) FROM matricula')->fetchColumn()===0,'Rollback por cruce');
        $db->exec('INSERT INTO prerequisito VALUES(1,2,1)');
        self::rejects(fn()=>$registrar->ejecutar(new RegistrarMatriculaDTO($u->getIdUsuario(),1,[2])),'Prerrequisito debe rechazarse');
        $m=$registrar->ejecutar(new RegistrarMatriculaDTO($u->getIdUsuario(),1,[1]));
        self::check((int)$db->query('SELECT vacantes_disponibles FROM seccion WHERE id_seccion=1')->fetchColumn()===9,'Descuento de vacante');
        self::check(count($mr->buscarPorId($m->getIdMatricula())->getDetalles())===1,'Recuperar detalles');
        self::rejects(fn()=>$registrar->ejecutar(new RegistrarMatriculaDTO($u->getIdUsuario(),1,[1])),'Matrícula duplicada');
        $mr->anularConDetalles($m->getIdMatricula());
        self::check((int)$db->query('SELECT vacantes_disponibles FROM seccion WHERE id_seccion=1')->fetchColumn()===10,'Devolución de vacante');
        self::rejects(fn()=>$mr->anularConDetalles($m->getIdMatricula()),'Anulación repetida');
        self::check((int)$db->query('SELECT vacantes_disponibles FROM seccion WHERE id_seccion=1')->fetchColumn()===10,'No duplicar vacantes');
        $db->exec('UPDATE seccion SET vacantes_disponibles=0 WHERE id_seccion=1');
        self::rejects(fn()=>$registrar->ejecutar(new RegistrarMatriculaDTO($u->getIdUsuario(),1,[1])),'Sin vacantes');
        \App\Infrastructure\Database\Connection::setInstance($db);
        $routes=require dirname(__DIR__).'/src/Presentation/Routes/api.php';
        $routes(new \App\Presentation\Routes\Router()); self::check(true,'Construcción completa de rutas');
        echo 'PASS: '.self::$checks." comprobaciones; SQLite temporal, sin modificar MySQL.\n";
    }
}

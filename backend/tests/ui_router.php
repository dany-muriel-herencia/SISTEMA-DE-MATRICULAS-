<?php
// Servidor aislado para pruebas manuales de interfaz. Nunca usa la conexión MySQL.
declare(strict_types=1);
if (PHP_SAPI !== 'cli-server' || !getenv('SGAU_TEST_DB')) { http_response_code(404); exit; }
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (str_starts_with($path, '/frontend/')) return false;
require dirname(__DIR__).'/vendor/autoload.php';
$db = new PDO('sqlite:'.getenv('SGAU_TEST_DB'), null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec('PRAGMA foreign_keys=ON');
if (!$db->query("SELECT name FROM sqlite_master WHERE name='usuario'")->fetch()) {
    $sql = file_get_contents(dirname(__DIR__,2).'/database/schema/01_initial_schema.sql');
    $sql = preg_replace('/CREATE DATABASE.*?;/s','',$sql);
    $sql = preg_replace('/^USE .*?;/m','',$sql);
    $db->exec(str_replace('INT AUTO_INCREMENT PRIMARY KEY','INTEGER PRIMARY KEY AUTOINCREMENT',$sql));
    $stmt=$db->prepare('INSERT INTO usuario (nombre,email,contrasenha,rol,estado) VALUES (?,?,?,?,1)');
    $stmt->execute(['Administrador de prueba','admin@example.test',password_hash('Prueba-local-2026',PASSWORD_DEFAULT),'ADMIN']);
    $stmt->execute(['Docente de prueba','docente@example.test',password_hash('Prueba-local-2026',PASSWORD_DEFAULT),'DOCENTE']);
    $db->exec("INSERT INTO docente VALUES(2,'D01','Sistemas','Doctor')");
    $db->exec("INSERT INTO periodo_academico VALUES(1,'2026-II','2026-08-01','2026-12-31','2026-08-01','2026-10-01','MATRICULA_ABIERTA')");
    $db->exec("INSERT INTO curso (id_curso,codigo,nombre,creditos,horas_teoria,horas_practica,ciclo,estado) VALUES(1,'CS101','Programación I',4,2,2,'I',1)");
    $db->exec("INSERT INTO aula VALUES(1,'Laboratorio 101',NULL,40,NULL,1,1)");
}
App\Infrastructure\Database\Connection::setInstance($db);
$router = new App\Presentation\Routes\Router();
(require dirname(__DIR__).'/src/Presentation/Routes/api.php')($router);
$router->dispatch($_SERVER['REQUEST_METHOD'], preg_replace('#^/backend/public#','',$path));

<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/vendor/autoload.php';
$env=dirname(__DIR__).'/.env';
if(is_file($env)) foreach(file($env,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line) {
    if(str_starts_with(trim($line),'#') || !str_contains($line,'='))continue;
    [$k,$v]=explode('=',$line,2); $_ENV[trim($k)]=trim($v," \t\n\r\0\x0B\"'");
}
function input(string $prompt): string { echo $prompt; return rtrim((string)fgets(STDIN),"\r\n"); }
try {
    $nombre=input('Nombre: '); $email=input('Correo: ');
    echo "La contraseña será visible en esta consola; no la comparta.\n";
    $password=input('Contraseña (mínimo 8 caracteres): ');
    $dto=new \App\Application\DTO\CrearUsuarioDTO($nombre,$email,$password,'ADMIN');
    $repo=new \App\Infrastructure\Repositories\MySQLUsuarioRepositorio(\App\Infrastructure\Database\Connection::getInstance());
    $u=(new \App\Application\CasoDeUso\Usuario\CrearUsuario($repo))->ejecutar($dto);
    echo 'Administrador creado, ID '.$u->getIdUsuario()."\n";
} catch(\Throwable $e) { fwrite(STDERR,$e->getMessage()."\n"); exit(1); }

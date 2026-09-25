<?php
declare(strict_types=1);
namespace App\Infrastructure\Repositories;
use App\Dominio\Entidades\Usuario;
use App\Dominio\Repositorios\UsuarioRepositorio;
final class MySQLUsuarioRepositorio extends MySQLRepositorioBase implements UsuarioRepositorio {
    public function buscarPorId(int $idUsuario): ?Usuario {
        $r=$this->one('SELECT * FROM usuario WHERE id_usuario = :id',['id'=>$idUsuario]);
        return $r ? $this->map($r) : null;
    }
    public function buscarPorEmail(string $email): ?Usuario {
        $r=$this->one('SELECT * FROM usuario WHERE email = :email',['email'=>$email]);
        return $r ? $this->map($r) : null;
    }
    public function listar(int $limit=50,int $offset=0): array {
        $limit=max(1,min(200,$limit)); $offset=max(0,$offset);
        return array_map(fn($r)=>$this->map($r),$this->all("SELECT * FROM usuario ORDER BY id_usuario LIMIT $limit OFFSET $offset"));
    }
    public function guardar(Usuario $u): void {
        $this->exec('INSERT INTO usuario(nombre,email,contrasenha,rol,estado,fecha_creacion) VALUES (:nombre,:email,:contrasenha,:rol,:estado,:fecha)', $this->params($u));
        $u->setIdUsuario($this->generatedId());
    }
    public function actualizar(Usuario $u): void {
        $this->exec('UPDATE usuario SET nombre=:nombre,email=:email,contrasenha=:contrasenha,rol=:rol,estado=:estado,fecha_creacion=:fecha WHERE id_usuario=:id', $this->params($u)+['id'=>$u->getIdUsuario()]);
    }
    public function eliminar(int $idUsuario): void { $this->exec('DELETE FROM usuario WHERE id_usuario=:id',['id'=>$idUsuario]); }
    private function params(Usuario $u): array {
        return ['nombre'=>$u->getNombre(),'email'=>$u->getEmail(),'contrasenha'=>$u->getContrasenha(),'rol'=>$u->getRol(),'estado'=>(int)$u->getEstado(),'fecha'=>$u->getFechaCreacion()->format('Y-m-d H:i:s')];
    }
    private function map(array $r): Usuario {
        return new Usuario((int)$r['id_usuario'],$r['nombre'],$r['email'],$r['contrasenha'],$r['rol'],(bool)$r['estado'],new \DateTimeImmutable($r['fecha_creacion']));
    }
}

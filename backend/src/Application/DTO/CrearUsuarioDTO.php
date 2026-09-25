<?php
declare(strict_types=1);
namespace App\Application\DTO;
final class CrearUsuarioDTO {
    public function __construct(private string $nombre,private string $email,private string $password,private string $rol) {
        if(trim($nombre)==='' || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($password)<8 || !in_array($rol,['ADMIN','COORDINADOR','DOCENTE','ESTUDIANTE'],true)) {
            throw new \InvalidArgumentException('Nombre, correo, rol o contraseña inválidos (mínimo 8 caracteres).');
        }
    }
    public static function fromArray(array $data): self {
        return new self(trim((string)($data['nombre']??'')),trim((string)($data['email']??'')),(string)($data['password']??''),(string)($data['rol']??'ESTUDIANTE'));
    }
    public function getNombre(): string { return $this->nombre; }
    public function getEmail(): string { return $this->email; }
    public function getPassword(): string { return $this->password; }
    public function getRol(): string { return $this->rol; }
}

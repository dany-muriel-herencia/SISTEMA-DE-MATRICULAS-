<?php

namespace App\Dominio\Entidades;

use DateTimeImmutable;
use InvalidArgumentException;

class Usuario
{
    protected int $idUsuario;
    protected string $nombre;
    protected string $email;
    protected string $contrasenha;
    protected string $rol;
    protected bool $estado;
    protected DateTimeImmutable $fechaCreacion;

    public function __construct(
        int $idUsuario,
        string $nombre,
        string $email,
        string $contrasenha,
        string $rol,
        bool $estado,
        DateTimeImmutable $fechaCreacion
    ) {
        if ($idUsuario < 0) {
            throw new InvalidArgumentException(
                'El ID del usuario debe ser mayor que cero'
            );
        }

        if (empty(trim($nombre))) {
            throw new InvalidArgumentException(
                'El nombre es obligatorio'
            );
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(
                'El correo electrónico no es válido'
            );
        }

        if (empty(trim($contrasenha))) {
            throw new InvalidArgumentException(
                'La contraseña es obligatoria'
            );
        }

        $this->idUsuario = $idUsuario;
        $this->nombre = $nombre;
        $this->email = $email;
        $this->contrasenha = $contrasenha;
        $this->cambiarRol($rol);
        $this->estado = $estado;
        $this->fechaCreacion = $fechaCreacion;
    }

    public function getIdUsuario(): int
    {
        return $this->idUsuario;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getContrasenha(): string
    {
        return $this->contrasenha;
    }

    public function getRol(): string
    {
        return $this->rol;
    }

    public function getEstado(): bool
    {
        return $this->estado;
    }

    public function getFechaCreacion(): DateTimeImmutable
    {
        return $this->fechaCreacion;
    }

    public function estaActivo(): bool
    {
        return $this->estado;
    }

    public function setIdUsuario(int $id): void { $this->idUsuario = $id; }
    public function cambiarRol(string $rol): void {
        if (!in_array($rol, ['ADMIN', 'COORDINADOR', 'DOCENTE', 'ESTUDIANTE'], true)) {
            throw new InvalidArgumentException('Rol inválido');
        }
        $this->rol = $rol;
    }
    public function cambiarContrasenha(string $hash): void { $this->contrasenha = $hash; }
    public function activar(): void { $this->estado = true; }
    public function desactivar(): void { $this->estado = false; }
    public function actualizarDatos(string $nombre, string $email): void {
        if (trim($nombre) === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Nombre o correo inválido');
        }
        $this->nombre = trim($nombre); $this->email = $email;
    }
    public function toArray(): array {
        return ['id_usuario'=>$this->idUsuario, 'nombre'=>$this->nombre, 'email'=>$this->email,
            'rol'=>$this->rol, 'estado'=>$this->estado, 'fecha_creacion'=>$this->fechaCreacion->format('Y-m-d H:i:s')];
    }

}

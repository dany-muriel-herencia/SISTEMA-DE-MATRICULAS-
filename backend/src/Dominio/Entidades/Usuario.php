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
                'El ID del usuario no puede ser negativo'
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
        $this->rol = $rol;
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

    public function isEstado(): bool
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

    public function cambiarRol(string $nuevoRol): void
    {
        $this->rol = $nuevoRol;
    }

    public function actualizarDatos(string $nombre, string $email): void
    {
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

        $this->nombre = $nombre;
        $this->email = $email;
    }

    public function desactivar(): void
    {
        $this->estado = false;
    }

    public function activar(): void
    {
        $this->estado = true;
    }

    public function cambiarContrasenha(string $nuevaContrasenha): void
    {
        if (empty(trim($nuevaContrasenha))) {
            throw new InvalidArgumentException(
                'La contraseña es obligatoria'
            );
        }

        $this->contrasenha = $nuevaContrasenha;
    }
}
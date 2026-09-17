<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Dominio\ValueObjects\Dni;
use App\Dominio\ValueObjects\Email;
use InvalidArgumentException;

final class CrearUsuarioDTO
{
    private int $rolId;
    private Dni $dni;
    private Email $email;
    private string $password;
    private string $nombre;
    private string $apellido;
    private ?string $telefono;

    public function __construct(
        int $rolId,
        string $dni,
        string $email,
        string $password,
        string $nombre,
        string $apellido,
        ?string $telefono = null
    ) {
        if ($rolId <= 0) {
            throw new InvalidArgumentException("El ID de rol debe ser un entero positivo.");
        }
        if (strlen($password) < 6) {
            throw new InvalidArgumentException("La contraseña debe tener al menos 6 caracteres.");
        }
        if (empty(trim($nombre))) {
            throw new InvalidArgumentException("El nombre es obligatorio.");
        }
        if (empty(trim($apellido))) {
            throw new InvalidArgumentException("El apellido es obligatorio.");
        }

        $this->rolId = $rolId;
        $this->dni = new Dni($dni);
        $this->email = new Email($email);
        $this->password = $password;
        $this->nombre = trim($nombre);
        $this->apellido = trim($apellido);
        $this->telefono = $telefono ? trim($telefono) : null;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int)($data['rol_id'] ?? 0),
            (string)($data['dni'] ?? ''),
            (string)($data['email'] ?? ''),
            (string)($data['password'] ?? ''),
            (string)($data['nombre'] ?? ''),
            (string)($data['apellido'] ?? ''),
            isset($data['telefono']) ? (string)$data['telefono'] : null
        );
    }

    public function getRolId(): int
    {
        return $this->rolId;
    }

    public function getDni(): Dni
    {
        return $this->dni;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getApellido(): string
    {
        return $this->apellido;
    }

    public function getTelefono(): ?string
    {
        return $this->telefono;
    }
}

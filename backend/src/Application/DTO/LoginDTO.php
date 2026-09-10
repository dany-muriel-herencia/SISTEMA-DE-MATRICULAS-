<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\ValueObjects\Email;
use InvalidArgumentException;

final class LoginDTO
{
    private Email $email;
    private string $password;

    public function __construct(string $email, string $password)
    {
        $this->email = new Email($email);
        $password = trim($password);
        if (empty($password)) {
            throw new InvalidArgumentException("La contraseña no puede estar vacía.");
        }
        $this->password = $password;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (string)($data['email'] ?? ''),
            (string)($data['password'] ?? '')
        );
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}

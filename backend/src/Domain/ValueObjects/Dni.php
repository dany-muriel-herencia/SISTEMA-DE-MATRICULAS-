<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

use InvalidArgumentException;

final class Dni
{
    private string $value;

    public function __construct(string $value)
    {
        $value = trim($value);
        if (!preg_match('/^\d{8}$/', $value)) {
            throw new InvalidArgumentException("El DNI debe contener exactamente 8 dígitos numéricos.");
        }
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->getValue();
    }
}

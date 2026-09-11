<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

use InvalidArgumentException;

final class CodigoMatricula
{
    private string $value;

    public function __construct(string $value)
    {
        $value = trim($value);
        if (empty($value)) {
            throw new InvalidArgumentException("El código de matrícula no puede estar vacío.");
        }
        $this->value = strtoupper($value);
    }

    public static function generar(int $periodoId, int $estudianteId): self
    {
        $uniqueSuffix = strtoupper(substr(uniqid(), -4));
        return new self(sprintf("MAT-%d-%d-%s", $periodoId, $estudianteId, $uniqueSuffix));
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

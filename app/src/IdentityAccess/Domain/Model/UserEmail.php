<?php

declare(strict_types=1);

namespace App\IdentityAccess\Domain\Model;

final readonly class UserEmail
{
    public function __construct(private string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email: ' . $value);
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

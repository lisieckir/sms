<?php

declare(strict_types=1);

namespace App\IdentityAccess\Domain\Model;

final readonly class UserPassword
{
    public function __construct(private string $hashedValue)
    {
    }

    public function hashedValue(): string
    {
        return $this->hashedValue;
    }

    public function __toString(): string
    {
        return $this->hashedValue;
    }
}

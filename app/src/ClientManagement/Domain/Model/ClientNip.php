<?php

declare(strict_types=1);

namespace App\ClientManagement\Domain\Model;

final readonly class ClientNip
{
    public function __construct(private string $value)
    {
        if (!preg_match('/^[A-Za-z0-9\-]{4,20}$/', $value)) {
            throw new \InvalidArgumentException('NIP must be 4-20 alphanumeric characters');
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

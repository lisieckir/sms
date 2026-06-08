<?php

declare(strict_types=1);

namespace App\ClientManagement\Domain\Model;

final readonly class ClientNip
{
    public function __construct(private string $value)
    {
        if (!preg_match('/^\d{10}$/', $value)) {
            throw new \InvalidArgumentException('NIP must be exactly 10 digits');
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

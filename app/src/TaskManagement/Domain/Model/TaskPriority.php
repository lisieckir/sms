<?php

declare(strict_types=1);

namespace App\TaskManagement\Domain\Model;

final readonly class TaskPriority
{
    private const VALID = ['low', 'medium', 'high', 'critical'];

    public function __construct(private string $value)
    {
        if (!in_array($value, self::VALID, true)) {
            throw new \InvalidArgumentException("Invalid priority: $value. Allowed: " . implode(', ', self::VALID));
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

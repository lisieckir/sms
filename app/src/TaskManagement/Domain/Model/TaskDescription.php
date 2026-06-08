<?php

declare(strict_types=1);

namespace App\TaskManagement\Domain\Model;

final readonly class TaskDescription
{
    public function __construct(private string $value)
    {
        if (mb_strlen($value) > 5000) {
            throw new \InvalidArgumentException('Description too long (max 5000 characters)');
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

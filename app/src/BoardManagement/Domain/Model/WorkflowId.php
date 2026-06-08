<?php

declare(strict_types=1);

namespace App\BoardManagement\Domain\Model;

use Symfony\Component\Uid\Uuid;

final readonly class WorkflowId
{
    public function __construct(private string $value)
    {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException('Invalid WorkflowId: ' . $value);
        }
    }

    public static function generate(): self
    {
        return new self(Uuid::v4()->toRfc4122());
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

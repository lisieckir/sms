<?php

declare(strict_types=1);

namespace App\BoardManagement\Domain\Model;

use Symfony\Component\Uid\Uuid;

final readonly class Stage
{
    public function __construct(
        private string $id,
        private string $name,
        private int $position,
    ) {
        if (empty($name)) {
            throw new \InvalidArgumentException('Stage name cannot be empty');
        }
    }

    public static function create(string $name, int $position): self
    {
        return new self(Uuid::v4()->toRfc4122(), $name, $position);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function rename(string $name): self
    {
        return new self($this->id, $name, $this->position);
    }

    public function move(int $position): self
    {
        return new self($this->id, $this->name, $position);
    }
}

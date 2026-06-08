<?php

declare(strict_types=1);

namespace App\BoardManagement\Application\DTO;

final class StageDTO
{
    public function __construct(
        private string $id,
        private string $name,
        private int $position,
        private array $tasks = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['name'],
            $data['position'],
            $data['tasks'] ?? [],
        );
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

    public function tasks(): array
    {
        return $this->tasks;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'position' => $this->position,
            'tasks' => $this->tasks,
        ];
    }
}

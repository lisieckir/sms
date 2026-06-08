<?php

declare(strict_types=1);

namespace App\TaskManagement\Application\Command\CreateTask;

final class CreateTaskCommand
{
    public function __construct(
        private string $title,
        private string $description,
        private string $creatorId,
        private string $stageId,
        private int $position,
        private ?string $assigneeId = null,
        private ?string $clientId = null,
        private ?string $parentTaskId = null,
    ) {}

    public function title(): string { return $this->title; }
    public function description(): string { return $this->description; }
    public function creatorId(): string { return $this->creatorId; }
    public function stageId(): string { return $this->stageId; }
    public function position(): int { return $this->position; }
    public function assigneeId(): ?string { return $this->assigneeId; }
    public function clientId(): ?string { return $this->clientId; }
    public function parentTaskId(): ?string { return $this->parentTaskId; }
}

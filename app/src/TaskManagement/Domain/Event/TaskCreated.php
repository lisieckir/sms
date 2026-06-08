<?php

declare(strict_types=1);

namespace App\TaskManagement\Domain\Event;

use App\TaskManagement\Domain\Model\TaskId;

final class TaskCreated implements DomainEvent
{
    public function __construct(
        private TaskId $taskId,
        private string $title,
        private string $creatorId,
        private string $stageId,
        private \DateTimeImmutable $occurredAt,
    ) {}

    public function taskId(): TaskId { return $this->taskId; }
    public function title(): string { return $this->title; }
    public function creatorId(): string { return $this->creatorId; }
    public function stageId(): string { return $this->stageId; }
    public function occurredAt(): \DateTimeImmutable { return $this->occurredAt; }
}

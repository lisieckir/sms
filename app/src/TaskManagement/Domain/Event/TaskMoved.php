<?php

declare(strict_types=1);

namespace App\TaskManagement\Domain\Event;

use App\TaskManagement\Domain\Model\TaskId;

final class TaskMoved implements DomainEvent
{
    public function __construct(
        private TaskId $taskId,
        private string $fromStageId,
        private string $toStageId,
        private \DateTimeImmutable $occurredAt,
    ) {}

    public function taskId(): TaskId { return $this->taskId; }
    public function fromStageId(): string { return $this->fromStageId; }
    public function toStageId(): string { return $this->toStageId; }
    public function occurredAt(): \DateTimeImmutable { return $this->occurredAt; }
}

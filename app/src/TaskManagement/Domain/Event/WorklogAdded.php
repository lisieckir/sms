<?php

declare(strict_types=1);

namespace App\TaskManagement\Domain\Event;

use App\TaskManagement\Domain\Model\TaskId;

final class WorklogAdded implements DomainEvent
{
    public function __construct(
        private TaskId $taskId,
        private string $worklogId,
        private string $userId,
        private int $minutes,
        private \DateTimeImmutable $occurredAt,
    ) {}

    public function taskId(): TaskId { return $this->taskId; }
    public function worklogId(): string { return $this->worklogId; }
    public function userId(): string { return $this->userId; }
    public function minutes(): int { return $this->minutes; }
    public function occurredAt(): \DateTimeImmutable { return $this->occurredAt; }
}

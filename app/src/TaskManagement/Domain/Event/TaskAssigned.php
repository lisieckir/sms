<?php

declare(strict_types=1);

namespace App\TaskManagement\Domain\Event;

use App\TaskManagement\Domain\Model\TaskId;

final class TaskAssigned implements DomainEvent
{
    public function __construct(
        private TaskId $taskId,
        private ?string $oldAssigneeId,
        private ?string $newAssigneeId,
        private \DateTimeImmutable $occurredAt,
    ) {}

    public function taskId(): TaskId { return $this->taskId; }
    public function oldAssigneeId(): ?string { return $this->oldAssigneeId; }
    public function newAssigneeId(): ?string { return $this->newAssigneeId; }
    public function occurredAt(): \DateTimeImmutable { return $this->occurredAt; }
}

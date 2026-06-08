<?php

declare(strict_types=1);

namespace App\TaskManagement\Domain\Event;

use App\TaskManagement\Domain\Model\TaskId;

final class TaskDescriptionChanged implements DomainEvent
{
    public function __construct(
        private TaskId $taskId,
        private string $oldDescription,
        private string $newDescription,
        private \DateTimeImmutable $occurredAt,
    ) {}

    public function taskId(): TaskId { return $this->taskId; }
    public function oldDescription(): string { return $this->oldDescription; }
    public function newDescription(): string { return $this->newDescription; }
    public function occurredAt(): \DateTimeImmutable { return $this->occurredAt; }
}

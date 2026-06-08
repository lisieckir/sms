<?php

declare(strict_types=1);

namespace App\TaskManagement\Domain\Event;

use App\TaskManagement\Domain\Model\TaskId;

final class TaskClientChanged implements DomainEvent
{
    public function __construct(
        private TaskId $taskId,
        private ?string $oldClientId,
        private ?string $newClientId,
        private \DateTimeImmutable $occurredAt,
    ) {}

    public function taskId(): TaskId { return $this->taskId; }
    public function oldClientId(): ?string { return $this->oldClientId; }
    public function newClientId(): ?string { return $this->newClientId; }
    public function occurredAt(): \DateTimeImmutable { return $this->occurredAt; }
}

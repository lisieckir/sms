<?php

declare(strict_types=1);

namespace App\TaskManagement\Domain\Event;

use App\TaskManagement\Domain\Model\TaskId;

final class CommentAdded implements DomainEvent
{
    public function __construct(
        private TaskId $taskId,
        private string $commentId,
        private string $userId,
        private \DateTimeImmutable $occurredAt,
    ) {}

    public function taskId(): TaskId { return $this->taskId; }
    public function commentId(): string { return $this->commentId; }
    public function userId(): string { return $this->userId; }
    public function occurredAt(): \DateTimeImmutable { return $this->occurredAt; }
}

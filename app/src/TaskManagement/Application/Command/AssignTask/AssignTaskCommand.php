<?php

declare(strict_types=1);

namespace App\TaskManagement\Application\Command\AssignTask;

final class AssignTaskCommand
{
    public function __construct(
        private string $taskId,
        private ?string $assigneeId,
    ) {}

    public function taskId(): string { return $this->taskId; }
    public function assigneeId(): ?string { return $this->assigneeId; }
}

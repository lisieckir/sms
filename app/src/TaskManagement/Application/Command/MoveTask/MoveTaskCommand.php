<?php

declare(strict_types=1);

namespace App\TaskManagement\Application\Command\MoveTask;

final class MoveTaskCommand
{
    public function __construct(
        private string $taskId,
        private string $stageId,
        private int $position,
    ) {}

    public function taskId(): string { return $this->taskId; }
    public function stageId(): string { return $this->stageId; }
    public function position(): int { return $this->position; }
}

<?php

declare(strict_types=1);

namespace App\TaskManagement\Application\Command\AddWorklog;

final class AddWorklogCommand
{
    public function __construct(
        private string $taskId,
        private string $userId,
        private int $minutes,
        private string $description,
    ) {}

    public function taskId(): string { return $this->taskId; }
    public function userId(): string { return $this->userId; }
    public function minutes(): int { return $this->minutes; }
    public function description(): string { return $this->description; }
}

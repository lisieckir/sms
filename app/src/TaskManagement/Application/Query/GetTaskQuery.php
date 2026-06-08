<?php

declare(strict_types=1);

namespace App\TaskManagement\Application\Query;

final class GetTaskQuery
{
    public function __construct(
        private string $taskId,
    ) {}

    public function taskId(): string { return $this->taskId; }
}

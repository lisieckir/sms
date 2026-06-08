<?php

declare(strict_types=1);

namespace App\TaskManagement\Application\Command\EditDescription;

final class EditDescriptionCommand
{
    public function __construct(
        private string $taskId,
        private string $description,
    ) {}

    public function taskId(): string { return $this->taskId; }
    public function description(): string { return $this->description; }
}

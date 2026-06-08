<?php

declare(strict_types=1);

namespace App\TaskManagement\Application\Command\AssignClient;

final readonly class AssignClientCommand
{
    public function __construct(
        private string $taskId,
        private ?string $clientId,
    ) {}

    public function taskId(): string { return $this->taskId; }
    public function clientId(): ?string { return $this->clientId; }
}

<?php

declare(strict_types=1);

namespace App\BoardManagement\Application\Command\CreateWorkflow;

final class CreateWorkflowCommand
{
    public function __construct(
        private string $name,
        private bool $isDefault = false,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }
}

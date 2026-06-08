<?php

declare(strict_types=1);

namespace App\BoardManagement\Application\Command\AddStage;

final class AddStageCommand
{
    public function __construct(
        private string $workflowId,
        private string $name,
        private int $position,
    ) {}

    public function workflowId(): string
    {
        return $this->workflowId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function position(): int
    {
        return $this->position;
    }
}

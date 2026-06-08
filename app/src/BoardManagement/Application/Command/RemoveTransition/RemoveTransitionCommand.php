<?php

declare(strict_types=1);

namespace App\BoardManagement\Application\Command\RemoveTransition;

final class RemoveTransitionCommand
{
    public function __construct(
        private string $workflowId,
        private string $fromStageId,
        private string $toStageId,
    ) {}

    public function workflowId(): string
    {
        return $this->workflowId;
    }

    public function fromStageId(): string
    {
        return $this->fromStageId;
    }

    public function toStageId(): string
    {
        return $this->toStageId;
    }
}

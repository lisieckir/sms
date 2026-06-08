<?php

declare(strict_types=1);

namespace App\BoardManagement\Application\Command\RemoveStage;

final class RemoveStageCommand
{
    public function __construct(
        private string $workflowId,
        private string $stageId,
    ) {}

    public function workflowId(): string
    {
        return $this->workflowId;
    }

    public function stageId(): string
    {
        return $this->stageId;
    }
}

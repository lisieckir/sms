<?php

declare(strict_types=1);

namespace App\BoardManagement\Application\Command\ReorderStage;

final class ReorderStageCommand
{
    public function __construct(
        private string $workflowId,
        private string $stageId,
        private int $newPosition,
    ) {}

    public function workflowId(): string { return $this->workflowId; }
    public function stageId(): string { return $this->stageId; }
    public function newPosition(): int { return $this->newPosition; }
}

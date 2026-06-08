<?php

declare(strict_types=1);

namespace App\BoardManagement\Application\Command\ReorderStage;

use App\BoardManagement\Domain\Model\WorkflowId;
use App\BoardManagement\Domain\Model\WorkflowRepositoryInterface;

final class ReorderStageHandler
{
    public function __construct(
        private WorkflowRepositoryInterface $workflowRepository,
    ) {}

    public function __invoke(ReorderStageCommand $command): void
    {
        $workflow = $this->workflowRepository->findById(new WorkflowId($command->workflowId()));
        if ($workflow === null) {
            throw new \InvalidArgumentException('Workflow not found');
        }

        $workflow->reorderStage($command->stageId(), $command->newPosition());
        $this->workflowRepository->save($workflow);
    }
}

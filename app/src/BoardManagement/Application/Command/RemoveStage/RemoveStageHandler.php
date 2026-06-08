<?php

declare(strict_types=1);

namespace App\BoardManagement\Application\Command\RemoveStage;

use App\BoardManagement\Domain\Model\WorkflowId;
use App\BoardManagement\Domain\Model\WorkflowRepositoryInterface;

final class RemoveStageHandler
{
    public function __construct(
        private WorkflowRepositoryInterface $workflowRepository,
    ) {}

    public function __invoke(RemoveStageCommand $command): void
    {
        $workflow = $this->workflowRepository->findById(new WorkflowId($command->workflowId()));
        if ($workflow === null) {
            throw new \InvalidArgumentException('Workflow not found');
        }

        $workflow->removeStage($command->stageId());
        $this->workflowRepository->save($workflow);
    }
}

<?php

declare(strict_types=1);

namespace App\BoardManagement\Application\Command\RemoveTransition;

use App\BoardManagement\Domain\Model\WorkflowId;
use App\BoardManagement\Domain\Model\WorkflowRepositoryInterface;

final class RemoveTransitionHandler
{
    public function __construct(
        private WorkflowRepositoryInterface $workflowRepository,
    ) {}

    public function __invoke(RemoveTransitionCommand $command): void
    {
        $workflow = $this->workflowRepository->findById(new WorkflowId($command->workflowId()));
        if ($workflow === null) {
            throw new \InvalidArgumentException('Workflow not found');
        }

        $workflow->removeTransition($command->fromStageId(), $command->toStageId());
        $this->workflowRepository->save($workflow);
    }
}

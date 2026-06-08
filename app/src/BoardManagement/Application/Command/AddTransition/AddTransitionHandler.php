<?php

declare(strict_types=1);

namespace App\BoardManagement\Application\Command\AddTransition;

use App\BoardManagement\Domain\Model\WorkflowId;
use App\BoardManagement\Domain\Model\WorkflowRepositoryInterface;

final class AddTransitionHandler
{
    public function __construct(
        private WorkflowRepositoryInterface $workflowRepository,
    ) {}

    public function __invoke(AddTransitionCommand $command): void
    {
        $workflow = $this->workflowRepository->findById(new WorkflowId($command->workflowId()));
        if ($workflow === null) {
            throw new \InvalidArgumentException('Workflow not found');
        }

        $workflow->addTransition($command->fromStageId(), $command->toStageId());
        $this->workflowRepository->save($workflow);
    }
}

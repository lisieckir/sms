<?php

declare(strict_types=1);

namespace App\BoardManagement\Application\Command\AddStage;

use App\BoardManagement\Domain\Model\Workflow;
use App\BoardManagement\Domain\Model\WorkflowId;
use App\BoardManagement\Domain\Model\WorkflowRepositoryInterface;

final class AddStageHandler
{
    public function __construct(
        private WorkflowRepositoryInterface $workflowRepository,
    ) {}

    public function __invoke(AddStageCommand $command): void
    {
        $workflow = $this->workflowRepository->findById(new WorkflowId($command->workflowId()));
        if ($workflow === null) {
            throw new \InvalidArgumentException('Workflow not found');
        }

        $workflow->addStage($command->name(), $command->position());
        $this->workflowRepository->save($workflow);
    }
}

<?php

declare(strict_types=1);

namespace App\BoardManagement\Application\Command\CreateWorkflow;

use App\BoardManagement\Domain\Model\Workflow;
use App\BoardManagement\Domain\Model\WorkflowRepositoryInterface;

final class CreateWorkflowHandler
{
    public function __construct(
        private WorkflowRepositoryInterface $workflowRepository,
    ) {}

    public function __invoke(CreateWorkflowCommand $command): void
    {
        $id = $this->workflowRepository->nextIdentity();
        $workflow = Workflow::create($id, $command->name(), $command->isDefault());
        $this->workflowRepository->save($workflow);
    }
}

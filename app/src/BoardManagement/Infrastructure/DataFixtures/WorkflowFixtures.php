<?php

declare(strict_types=1);

namespace App\BoardManagement\Infrastructure\DataFixtures;

use App\BoardManagement\Domain\Model\Workflow;
use App\BoardManagement\Domain\Model\WorkflowRepositoryInterface;

final class WorkflowFixtures
{
    public function __construct(
        private WorkflowRepositoryInterface $workflowRepository,
    ) {}

    public function load(): void
    {
        if ($this->workflowRepository->findDefault() !== null) {
            return;
        }

        $id = $this->workflowRepository->nextIdentity();
        $workflow = Workflow::create($id, 'Default Workflow', isDefault: true);

        $workflow->addStage('Backlog', 0);
        $workflow->addStage('To Do', 1);
        $workflow->addStage('In Progress', 2);
        $workflow->addStage('Review', 3);
        $workflow->addStage('Done', 4);

        $stages = $workflow->sortedStages();
        $backlog = $stages[0]->id();
        $todo = $stages[1]->id();
        $inProgress = $stages[2]->id();
        $review = $stages[3]->id();
        $done = $stages[4]->id();

        $workflow->addTransition($backlog, $todo);
        $workflow->addTransition($todo, $inProgress);
        $workflow->addTransition($inProgress, $review);
        $workflow->addTransition($review, $done);

        $workflow->addTransition($todo, $backlog);
        $workflow->addTransition($inProgress, $todo);
        $workflow->addTransition($review, $inProgress);
        $workflow->addTransition($done, $review);

        $this->workflowRepository->save($workflow);
    }
}

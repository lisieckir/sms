<?php

declare(strict_types=1);

namespace App\TaskManagement\Application\Command\MoveTask;

use App\BoardManagement\Domain\Model\WorkflowRepositoryInterface;
use App\TaskManagement\Domain\Model\TaskId;
use App\TaskManagement\Domain\Model\TaskRepositoryInterface;
use App\TaskManagement\Infrastructure\Projection\TaskEventProjector;

final class MoveTaskHandler
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private WorkflowRepositoryInterface $workflowRepository,
        private TaskEventProjector $eventProjector,
    ) {}

    public function __invoke(MoveTaskCommand $command): void
    {
        $task = $this->taskRepository->findById(new TaskId($command->taskId()));
        if ($task === null) {
            throw new \InvalidArgumentException('Task not found');
        }

        $workflow = $this->workflowRepository->findDefault();
        if ($workflow === null) {
            throw new \RuntimeException('No default workflow configured');
        }

        $fromStageId = $task->stageId();
        $toStageId = $command->stageId();

        if ($fromStageId !== $toStageId && !$workflow->canTransition($fromStageId, $toStageId)) {
            throw new \InvalidArgumentException(
                "Transition from stage $fromStageId to $toStageId is not allowed"
            );
        }

        $task->moveToStage($command->stageId(), $command->position());
        $this->taskRepository->save($task);

        foreach ($task->releaseEvents() as $event) {
            $this->eventProjector->project($event);
        }
    }
}

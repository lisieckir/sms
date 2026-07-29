<?php

declare(strict_types=1);

namespace App\BoardManagement\Application\Query;

use App\BoardManagement\Application\DTO\StageDTO;
use App\BoardManagement\Domain\Model\WorkflowRepositoryInterface;
use App\TaskManagement\Domain\Model\TaskRepositoryInterface;

final class GetBoardHandler
{
    public function __construct(
        private WorkflowRepositoryInterface $workflowRepository,
        private TaskRepositoryInterface $taskRepository,
    ) {}

    public function __invoke(GetBoardQuery $query): array
    {
        $workflow = $this->workflowRepository->findDefault();
        if ($workflow === null) {
            return [];
        }

        $stages = [];
        foreach ($workflow->sortedStages() as $stage) {
            $tasks = $this->taskRepository->findByStage($stage->id());

            $taskData = [];
            foreach ($tasks as $task) {
                if ($query->clientId() !== null && $task->clientId() !== $query->clientId()) {
                    continue;
                }
                if ($query->assigneeId() !== null && $task->assigneeId() !== $query->assigneeId()) {
                    continue;
                }
                $taskData[] = [
                    'id' => $task->id()->value(),
                    'title' => $task->title(),
                    'assigneeId' => $task->assigneeId(),
                    'assigneeName' => null,
                    'clientId' => $task->clientId(),
                    'position' => $task->position(),
                    'priority' => $task->priority()->value(),
                ];
            }

            usort($taskData, fn(array $a, array $b) => $a['position'] <=> $b['position']);

            $stages[] = StageDTO::fromArray([
                'id' => $stage->id(),
                'name' => $stage->name(),
                'position' => $stage->position(),
                'tasks' => $taskData,
            ]);
        }

        return $stages;
    }
}

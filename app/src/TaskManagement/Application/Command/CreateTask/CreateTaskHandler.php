<?php

declare(strict_types=1);

namespace App\TaskManagement\Application\Command\CreateTask;

use App\TaskManagement\Domain\Model\Task;
use App\TaskManagement\Domain\Model\TaskDescription;
use App\TaskManagement\Domain\Model\TaskRepositoryInterface;
use App\TaskManagement\Infrastructure\Projection\TaskEventProjector;

final class CreateTaskHandler
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private TaskEventProjector $eventProjector,
    ) {}

    public function __invoke(CreateTaskCommand $command): string
    {
        $id = $this->taskRepository->nextIdentity();
        $task = Task::create(
            id: $id,
            title: $command->title(),
            description: new TaskDescription($command->description()),
            creatorId: $command->creatorId(),
            stageId: $command->stageId(),
            position: $command->position(),
            assigneeId: $command->assigneeId(),
            clientId: $command->clientId(),
            parentTaskId: $command->parentTaskId(),
        );
        $this->taskRepository->save($task);

        foreach ($task->releaseEvents() as $event) {
            $this->eventProjector->project($event);
        }

        return $id->value();
    }
}

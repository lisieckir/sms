<?php

declare(strict_types=1);

namespace App\TaskManagement\Application\Command\AddWorklog;

use App\TaskManagement\Domain\Model\TaskId;
use App\TaskManagement\Domain\Model\TaskRepositoryInterface;
use App\TaskManagement\Infrastructure\Projection\TaskEventProjector;

final class AddWorklogHandler
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private TaskEventProjector $eventProjector,
    ) {}

    public function __invoke(AddWorklogCommand $command): void
    {
        $task = $this->taskRepository->findById(new TaskId($command->taskId()));
        if ($task === null) {
            throw new \InvalidArgumentException('Task not found');
        }

        $task->addWorklog($command->userId(), $command->minutes(), $command->description());
        $this->taskRepository->save($task);

        foreach ($task->releaseEvents() as $event) {
            $this->eventProjector->project($event);
        }
    }
}

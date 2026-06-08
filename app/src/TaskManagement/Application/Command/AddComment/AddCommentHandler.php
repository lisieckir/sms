<?php

declare(strict_types=1);

namespace App\TaskManagement\Application\Command\AddComment;

use App\TaskManagement\Domain\Model\TaskId;
use App\TaskManagement\Domain\Model\TaskRepositoryInterface;
use App\TaskManagement\Infrastructure\Projection\TaskEventProjector;

final class AddCommentHandler
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private TaskEventProjector $eventProjector,
    ) {}

    public function __invoke(AddCommentCommand $command): void
    {
        $task = $this->taskRepository->findById(new TaskId($command->taskId()));
        if ($task === null) {
            throw new \InvalidArgumentException('Task not found');
        }

        $task->addComment($command->userId(), $command->content());
        $this->taskRepository->save($task);

        foreach ($task->releaseEvents() as $event) {
            $this->eventProjector->project($event);
        }
    }
}

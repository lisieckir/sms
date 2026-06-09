<?php

declare(strict_types=1);

namespace App\TaskManagement\Infrastructure\Projection;

use App\TaskManagement\Infrastructure\PocketBase\TaskEventStore;
use App\TaskManagement\Domain\Event\CommentAdded;
use App\TaskManagement\Domain\Event\TaskAssigned;
use App\TaskManagement\Domain\Event\TaskClientChanged;
use App\TaskManagement\Domain\Event\TaskCreated;
use App\TaskManagement\Domain\Event\TaskDescriptionChanged;
use App\TaskManagement\Domain\Event\TaskMoved;
use App\TaskManagement\Domain\Event\WorklogAdded;
use Symfony\Component\Uid\Uuid;

class TaskEventProjector
{
    public function __construct(
        private TaskEventStore $eventStore,
    ) {}

    public function project(object $event): void
    {
        match (true) {
            $event instanceof TaskCreated => $this->projectTaskCreated($event),
            $event instanceof TaskMoved => $this->projectTaskMoved($event),
            $event instanceof TaskAssigned => $this->projectTaskAssigned($event),
            $event instanceof TaskClientChanged => $this->projectTaskClientChanged($event),
            $event instanceof TaskDescriptionChanged => $this->projectTaskDescriptionChanged($event),
            $event instanceof CommentAdded => $this->projectCommentAdded($event),
            $event instanceof WorklogAdded => $this->projectWorklogAdded($event),
            default => null,
        };
    }

    private function projectTaskCreated(TaskCreated $event): void
    {
        $this->eventStore->append(
            eventId: Uuid::v4()->toRfc4122(),
            taskId: $event->taskId()->value(),
            userId: $event->creatorId(),
            type: 'TaskCreated',
            data: ['title' => $event->title(), 'stageId' => $event->stageId()],
            occurredAt: $event->occurredAt(),
        );
    }

    private function projectTaskMoved(TaskMoved $event): void
    {
        $this->eventStore->append(
            eventId: Uuid::v4()->toRfc4122(),
            taskId: $event->taskId()->value(),
            userId: null,
            type: 'TaskMoved',
            data: ['fromStageId' => $event->fromStageId(), 'toStageId' => $event->toStageId()],
            occurredAt: $event->occurredAt(),
        );
    }

    private function projectTaskAssigned(TaskAssigned $event): void
    {
        $this->eventStore->append(
            eventId: Uuid::v4()->toRfc4122(),
            taskId: $event->taskId()->value(),
            userId: $event->newAssigneeId(),
            type: 'TaskAssigned',
            data: ['oldAssigneeId' => $event->oldAssigneeId(), 'newAssigneeId' => $event->newAssigneeId()],
            occurredAt: $event->occurredAt(),
        );
    }

    private function projectTaskClientChanged(TaskClientChanged $event): void
    {
        $this->eventStore->append(
            eventId: Uuid::v4()->toRfc4122(),
            taskId: $event->taskId()->value(),
            userId: null,
            type: 'TaskClientChanged',
            data: ['oldClientId' => $event->oldClientId(), 'newClientId' => $event->newClientId()],
            occurredAt: $event->occurredAt(),
        );
    }

    private function projectTaskDescriptionChanged(TaskDescriptionChanged $event): void
    {
        $this->eventStore->append(
            eventId: Uuid::v4()->toRfc4122(),
            taskId: $event->taskId()->value(),
            userId: null,
            type: 'TaskDescriptionChanged',
            data: ['oldDescription' => $event->oldDescription(), 'newDescription' => $event->newDescription()],
            occurredAt: $event->occurredAt(),
        );
    }

    private function projectCommentAdded(CommentAdded $event): void
    {
        $this->eventStore->append(
            eventId: Uuid::v4()->toRfc4122(),
            taskId: $event->taskId()->value(),
            userId: $event->userId(),
            type: 'CommentAdded',
            data: ['commentId' => $event->commentId()],
            occurredAt: $event->occurredAt(),
        );
    }

    private function projectWorklogAdded(WorklogAdded $event): void
    {
        $this->eventStore->append(
            eventId: Uuid::v4()->toRfc4122(),
            taskId: $event->taskId()->value(),
            userId: $event->userId(),
            type: 'WorklogAdded',
            data: ['minutes' => $event->minutes(), 'worklogId' => $event->worklogId()],
            occurredAt: $event->occurredAt(),
        );
    }
}

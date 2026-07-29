<?php

declare(strict_types=1);

namespace App\TaskManagement\Infrastructure\PocketBase;

use App\Core\Infrastructure\PocketBase\PocketBaseClient;
use App\TaskManagement\Domain\Model\Comment;
use App\TaskManagement\Domain\Model\Task;
use App\TaskManagement\Domain\Model\TaskDescription;
use App\TaskManagement\Domain\Model\TaskId;
use App\TaskManagement\Domain\Model\TaskPriority;
use App\TaskManagement\Domain\Model\TaskRepositoryInterface;
use App\TaskManagement\Domain\Model\Worklog;

final readonly class TaskRepository implements TaskRepositoryInterface
{
    private const COLLECTION = 'tasks';

    public function __construct(
        private PocketBaseClient $pb,
    ) {}

    public function save(Task $task): void
    {
        $data = [
            'taskId' => $task->id()->value(),
            'title' => $task->title(),
            'description' => $task->description()->value(),
            'creatorId' => $task->creatorId(),
            'assigneeId' => $task->assigneeId(),
            'clientId' => $task->clientId(),
            'stageId' => $task->stageId(),
            'position' => $task->position(),
            'parentTaskId' => $task->parentTaskId(),
            'priority' => $task->priority()->value(),
            'status' => $task->status(),
            'totalTimeSpent' => $task->totalTimeSpent(),
            'comments' => array_map(fn(Comment $c) => [
                'id' => $c->id(),
                'userId' => $c->userId(),
                'content' => $c->content(),
                'createdAt' => $c->createdAt()->format('c'),
                'editedAt' => $c->editedAt()?->format('c'),
            ], $task->comments()),
            'worklogs' => array_map(fn(Worklog $w) => [
                'id' => $w->id(),
                'userId' => $w->userId(),
                'minutes' => $w->minutes(),
                'description' => $w->description(),
                'date' => $w->date()->format('Y-m-d'),
                'createdAt' => $w->createdAt()->format('c'),
            ], $task->worklogs()),
            'created' => $task->createdAt()->format('c'),
            'updated' => $task->updatedAt()->format('c'),
        ];

        $this->pb->upsert(self::COLLECTION, sprintf('taskId="%s"', $task->id()->value()), $data);
    }

    public function findById(TaskId $id): ?Task
    {
        $record = $this->pb->first(self::COLLECTION, sprintf('taskId="%s"', $id->value()));
        return $record !== null ? $this->toDomain($record) : null;
    }

    public function findByStage(string $stageId): array
    {
        $result = $this->pb->list(self::COLLECTION, [
            'filter' => sprintf('stageId="%s"', $stageId),
            'sort' => 'position',
        ]);
        return array_map(fn(array $r) => $this->toDomain($r), $result['items']);
    }

    public function reindexStage(string $stageId): void
    {
        $tasks = $this->findByStage($stageId);
        foreach ($tasks as $i => $task) {
            $task->changePosition($i);
            $this->save($task);
        }
    }

    public function findByClient(string $clientId): array
    {
        $result = $this->pb->list(self::COLLECTION, [
            'filter' => sprintf('clientId="%s"', $clientId),
        ]);
        return array_map(fn(array $r) => $this->toDomain($r), $result['items']);
    }

    public function findByAssignee(string $assigneeId): array
    {
        $result = $this->pb->list(self::COLLECTION, [
            'filter' => sprintf('assigneeId="%s"', $assigneeId),
        ]);
        return array_map(fn(array $r) => $this->toDomain($r), $result['items']);
    }

    public function findByParent(TaskId $parentId): array
    {
        $result = $this->pb->list(self::COLLECTION, [
            'filter' => sprintf('parentTaskId="%s"', $parentId->value()),
        ]);
        return array_map(fn(array $r) => $this->toDomain($r), $result['items']);
    }

    public function findAll(): array
    {
        $result = $this->pb->list(self::COLLECTION);
        return array_map(fn(array $r) => $this->toDomain($r), $result['items']);
    }

    public function nextIdentity(): TaskId
    {
        return TaskId::generate();
    }

    private function toDomain(array $record): Task
    {
        $fields = $record;

        $task = new \ReflectionClass(Task::class);
        $instance = $task->newInstanceWithoutConstructor();

        $idProp = $task->getProperty('id');
        $idProp->setAccessible(true);
        $idProp->setValue($instance, new TaskId($fields['taskId']));

        $titleProp = $task->getProperty('title');
        $titleProp->setAccessible(true);
        $titleProp->setValue($instance, $fields['title']);

        $descProp = $task->getProperty('description');
        $descProp->setAccessible(true);
        $descProp->setValue($instance, new TaskDescription($fields['description']));

        $creatorProp = $task->getProperty('creatorId');
        $creatorProp->setAccessible(true);
        $creatorProp->setValue($instance, $fields['creatorId']);

        $assigneeProp = $task->getProperty('assigneeId');
        $assigneeProp->setAccessible(true);
        $assigneeProp->setValue($instance, $fields['assigneeId'] !== '' ? $fields['assigneeId'] : null);

        $clientProp = $task->getProperty('clientId');
        $clientProp->setAccessible(true);
        $clientProp->setValue($instance, $fields['clientId'] !== '' ? $fields['clientId'] : null);

        $stageProp = $task->getProperty('stageId');
        $stageProp->setAccessible(true);
        $stageProp->setValue($instance, $fields['stageId']);

        $posProp = $task->getProperty('position');
        $posProp->setAccessible(true);
        $posProp->setValue($instance, $fields['position']);

        $parentProp = $task->getProperty('parentTaskId');
        $parentProp->setAccessible(true);
        $parentProp->setValue($instance, $fields['parentTaskId'] !== '' ? $fields['parentTaskId'] : null);

        $priorityProp = $task->getProperty('priority');
        $priorityProp->setAccessible(true);
        $priorityProp->setValue($instance, new TaskPriority($fields['priority'] ?: 'medium'));

        $statusProp = $task->getProperty('status');
        $statusProp->setAccessible(true);
        $statusProp->setValue($instance, $fields['status']);

        $commentsData = $fields['comments'] ?? [];
        $commentsProp = $task->getProperty('comments');
        $commentsProp->setAccessible(true);
        $commentsProp->setValue($instance, array_map(
            fn(array $c) => new Comment(
                $c['id'],
                $c['userId'],
                $c['content'],
                new \DateTimeImmutable($c['createdAt']),
                isset($c['editedAt']) ? new \DateTimeImmutable($c['editedAt']) : null,
            ),
            $commentsData,
        ));

        $worklogsData = $fields['worklogs'] ?? [];
        $worklogsProp = $task->getProperty('worklogs');
        $worklogsProp->setAccessible(true);
        $worklogsProp->setValue($instance, array_map(
            fn(array $w) => Worklog::create(
                $w['userId'],
                $w['minutes'],
                $w['description'],
                new \DateTimeImmutable($w['date']),
            ),
            $worklogsData,
        ));

        $totalProp = $task->getProperty('totalTimeSpent');
        $totalProp->setAccessible(true);
        $totalProp->setValue($instance, $fields['totalTimeSpent']);

        $createdProp = $task->getProperty('createdAt');
        $createdProp->setAccessible(true);
        $createdProp->setValue($instance, new \DateTimeImmutable($fields['created']));

        $updatedProp = $task->getProperty('updatedAt');
        $updatedProp->setAccessible(true);
        $updatedProp->setValue($instance, new \DateTimeImmutable($fields['updated']));

        return $instance;
    }
}

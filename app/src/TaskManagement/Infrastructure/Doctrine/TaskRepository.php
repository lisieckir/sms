<?php

declare(strict_types=1);

namespace App\TaskManagement\Infrastructure\Doctrine;

use App\TaskManagement\Domain\Model\Comment;
use App\TaskManagement\Domain\Model\Task;
use App\TaskManagement\Domain\Model\TaskDescription;
use App\TaskManagement\Domain\Model\TaskId;
use App\TaskManagement\Domain\Model\TaskRepositoryInterface;
use App\TaskManagement\Domain\Model\Worklog;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

class TaskRepository implements TaskRepositoryInterface
{
    private DocumentRepository $repository;

    public function __construct(
        private DocumentManager $dm,
    ) {
        $this->repository = $dm->getRepository(TaskDocument::class);
    }

    public function save(Task $task): void
    {
        $doc = $this->repository->findOneBy(['taskId' => $task->id()->value()]);
        if ($doc === null) {
            $doc = new TaskDocument();
            $doc->taskId = $task->id()->value();
            $doc->createdAt = \DateTime::createFromImmutable($task->createdAt());
            $this->dm->persist($doc);
        }

        $doc->title = $task->title();
        $doc->description = $task->description()->value();
        $doc->creatorId = $task->creatorId();
        $doc->assigneeId = $task->assigneeId();
        $doc->clientId = $task->clientId();
        $doc->stageId = $task->stageId();
        $doc->position = $task->position();
        $doc->parentTaskId = $task->parentTaskId();
        $doc->status = $task->status();
        $doc->comments = array_map(fn(Comment $c) => [
            'id' => $c->id(),
            'userId' => $c->userId(),
            'content' => $c->content(),
            'createdAt' => $c->createdAt()->format('c'),
        ], $task->comments());
        $doc->worklogs = array_map(fn(Worklog $w) => [
            'id' => $w->id(),
            'userId' => $w->userId(),
            'minutes' => $w->minutes(),
            'description' => $w->description(),
            'date' => $w->date()->format('Y-m-d'),
            'createdAt' => $w->createdAt()->format('c'),
        ], $task->worklogs());
        $doc->totalTimeSpent = $task->totalTimeSpent();
        $doc->updatedAt = \DateTime::createFromImmutable($task->updatedAt());

        $this->dm->flush();
    }

    public function findById(TaskId $id): ?Task
    {
        $doc = $this->repository->findOneBy(['taskId' => $id->value()]);
        return $doc ? $this->toDomain($doc) : null;
    }

    public function findByStage(string $stageId): array
    {
        $docs = $this->repository->findBy(['stageId' => $stageId], ['position' => 'asc']);
        return array_map(fn(TaskDocument $doc) => $this->toDomain($doc), $docs);
    }

    public function findByClient(string $clientId): array
    {
        $docs = $this->repository->findBy(['clientId' => $clientId]);
        return array_map(fn(TaskDocument $doc) => $this->toDomain($doc), $docs);
    }

    public function findByAssignee(string $assigneeId): array
    {
        $docs = $this->repository->findBy(['assigneeId' => $assigneeId]);
        return array_map(fn(TaskDocument $doc) => $this->toDomain($doc), $docs);
    }

    public function findByParent(TaskId $parentId): array
    {
        $docs = $this->repository->findBy(['parentTaskId' => $parentId->value()]);
        return array_map(fn(TaskDocument $doc) => $this->toDomain($doc), $docs);
    }

    public function findAll(): array
    {
        $docs = $this->repository->findAll();
        return array_map(fn(TaskDocument $doc) => $this->toDomain($doc), $docs);
    }

    public function nextIdentity(): TaskId
    {
        return TaskId::generate();
    }

    private function toDomain(TaskDocument $doc): Task
    {
        $task = new \ReflectionClass(Task::class);
        $instance = $task->newInstanceWithoutConstructor();

        $idProp = $task->getProperty('id');
        $idProp->setAccessible(true);
        $idProp->setValue($instance, new TaskId($doc->taskId));

        $titleProp = $task->getProperty('title');
        $titleProp->setAccessible(true);
        $titleProp->setValue($instance, $doc->title);

        $descProp = $task->getProperty('description');
        $descProp->setAccessible(true);
        $descProp->setValue($instance, new TaskDescription($doc->description));

        $creatorProp = $task->getProperty('creatorId');
        $creatorProp->setAccessible(true);
        $creatorProp->setValue($instance, $doc->creatorId);

        $assigneeProp = $task->getProperty('assigneeId');
        $assigneeProp->setAccessible(true);
        $assigneeProp->setValue($instance, $doc->assigneeId);

        $clientProp = $task->getProperty('clientId');
        $clientProp->setAccessible(true);
        $clientProp->setValue($instance, $doc->clientId);

        $stageProp = $task->getProperty('stageId');
        $stageProp->setAccessible(true);
        $stageProp->setValue($instance, $doc->stageId);

        $posProp = $task->getProperty('position');
        $posProp->setAccessible(true);
        $posProp->setValue($instance, $doc->position);

        $parentProp = $task->getProperty('parentTaskId');
        $parentProp->setAccessible(true);
        $parentProp->setValue($instance, $doc->parentTaskId);

        $statusProp = $task->getProperty('status');
        $statusProp->setAccessible(true);
        $statusProp->setValue($instance, $doc->status);

        $commentsProp = $task->getProperty('comments');
        $commentsProp->setAccessible(true);
        $commentsProp->setValue($instance, array_map(
            fn(array $c) => new Comment(
                $c['id'],
                $c['userId'],
                $c['content'],
                new \DateTimeImmutable($c['createdAt']),
            ),
            $doc->comments,
        ));

        $worklogsProp = $task->getProperty('worklogs');
        $worklogsProp->setAccessible(true);
        $worklogsProp->setValue($instance, array_map(
            fn(array $w) => Worklog::create(
                $w['userId'],
                $w['minutes'],
                $w['description'],
                new \DateTimeImmutable($w['date']),
            ),
            $doc->worklogs,
        ));

        $totalProp = $task->getProperty('totalTimeSpent');
        $totalProp->setAccessible(true);
        $totalProp->setValue($instance, $doc->totalTimeSpent);

        $createdProp = $task->getProperty('createdAt');
        $createdProp->setAccessible(true);
        $createdProp->setValue($instance, \DateTimeImmutable::createFromMutable($doc->createdAt));

        $updatedProp = $task->getProperty('updatedAt');
        $updatedProp->setAccessible(true);
        $updatedProp->setValue($instance, \DateTimeImmutable::createFromMutable($doc->updatedAt));

        return $instance;
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\InMemory;

use App\TaskManagement\Domain\Model\Task;
use App\TaskManagement\Domain\Model\TaskId;
use App\TaskManagement\Domain\Model\TaskRepositoryInterface;

class InMemoryTaskRepository implements TaskRepositoryInterface
{
    /** @var array<string, Task> */
    private array $tasks = [];

    public function save(Task $task): void
    {
        $this->tasks[$task->id()->value()] = $task;
    }

    public function findById(TaskId $id): ?Task
    {
        return $this->tasks[$id->value()] ?? null;
    }

    public function findByStage(string $stageId): array
    {
        return array_values(array_filter($this->tasks, fn(Task $t) => $t->stageId() === $stageId));
    }

    public function findByClient(string $clientId): array
    {
        return array_values(array_filter($this->tasks, fn(Task $t) => $t->clientId() === $clientId));
    }

    public function findByAssignee(string $assigneeId): array
    {
        return array_values(array_filter($this->tasks, fn(Task $t) => $t->assigneeId() === $assigneeId));
    }

    public function findByParent(TaskId $parentId): array
    {
        return array_values(array_filter($this->tasks, fn(Task $t) => $t->parentTaskId() === $parentId->value()));
    }

    public function findAll(): array
    {
        return array_values($this->tasks);
    }

    public function reindexStage(string $stageId): void
    {
        $tasks = $this->findByStage($stageId);
        usort($tasks, fn(Task $a, Task $b) => $a->position() <=> $b->position());
        foreach ($tasks as $i => $task) {
            $task->changePosition($i);
            $this->save($task);
        }
    }

    public function nextIdentity(): TaskId
    {
        return TaskId::generate();
    }
}

<?php

declare(strict_types=1);

namespace App\TaskManagement\Domain\Model;

interface TaskRepositoryInterface
{
    public function save(Task $task): void;
    public function findById(TaskId $id): ?Task;
    public function findByStage(string $stageId): array;
    public function reindexStage(string $stageId): void;
    public function findByClient(string $clientId): array;
    public function findByAssignee(string $assigneeId): array;
    public function findByParent(TaskId $parentId): array;
    public function findAll(): array;
    public function nextIdentity(): TaskId;
}

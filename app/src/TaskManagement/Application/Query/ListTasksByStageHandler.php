<?php

declare(strict_types=1);

namespace App\TaskManagement\Application\Query;

use App\Core\UI\Twig\MarkdownConverter;
use App\TaskManagement\Application\DTO\TaskDTO;
use App\TaskManagement\Domain\Model\TaskRepositoryInterface;

final class ListTasksByStageHandler
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private MarkdownConverter $markdownConverter,
    ) {}

    public function __invoke(ListTasksByStageQuery $query): array
    {
        $tasks = $this->taskRepository->findByStage($query->stageId());
        return array_map(function ($task) {
            $description = $task->description()->value();
            return TaskDTO::fromArray([
                'id' => $task->id()->value(),
                'title' => $task->title(),
                'description' => $description,
                'descriptionHtml' => $this->markdownConverter->toHtml($description),
                'creatorId' => $task->creatorId(),
                'assigneeId' => $task->assigneeId(),
                'assigneeName' => null,
                'clientId' => $task->clientId(),
                'clientName' => null,
                'stageId' => $task->stageId(),
                'position' => $task->position(),
                'parentTaskId' => $task->parentTaskId(),
                'totalTimeSpent' => $task->totalTimeSpent(),
                'status' => $task->status(),
                'comments' => [],
                'worklogs' => [],
                'createdAt' => $task->createdAt()->format('c'),
                'updatedAt' => $task->updatedAt()->format('c'),
            ]);
        }, $tasks);
    }
}

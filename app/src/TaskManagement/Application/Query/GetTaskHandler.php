<?php

declare(strict_types=1);

namespace App\TaskManagement\Application\Query;

use App\BoardManagement\Domain\Model\WorkflowRepositoryInterface;
use App\ClientManagement\Domain\Model\ClientRepositoryInterface;
use App\IdentityAccess\Domain\Model\UserRepositoryInterface;
use App\TaskManagement\Application\DTO\TaskDTO;
use App\TaskManagement\Domain\Model\TaskId;
use App\TaskManagement\Domain\Model\TaskRepositoryInterface;

final class GetTaskHandler
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private WorkflowRepositoryInterface $workflowRepository,
        private UserRepositoryInterface $userRepository,
        private ClientRepositoryInterface $clientRepository,
    ) {}

    public function __invoke(GetTaskQuery $query): ?TaskDTO
    {
        $task = $this->taskRepository->findById(new TaskId($query->taskId()));
        if ($task === null) {
            return null;
        }

        $workflow = $this->workflowRepository->findDefault();
        $stageName = $task->stageId();
        if ($workflow !== null) {
            foreach ($workflow->sortedStages() as $stage) {
                if ($stage->id() === $task->stageId()) {
                    $stageName = $stage->name();
                    break;
                }
            }
        }

        $userNames = [];
        $resolveUser = function (string $userId) use (&$userNames): string {
            if (!isset($userNames[$userId])) {
                $user = $this->userRepository->findById(new \App\IdentityAccess\Domain\Model\UserId($userId));
                $userNames[$userId] = $user ? $user->username() : $userId;
            }
            return $userNames[$userId];
        };

        $comments = array_map(fn($c) => [
            'id' => $c->id(),
            'userId' => $c->userId(),
            'userName' => $resolveUser($c->userId()),
            'content' => $c->content(),
            'createdAt' => $c->createdAt()->format('c'),
        ], $task->comments());

        $worklogs = array_map(fn($w) => [
            'id' => $w->id(),
            'userId' => $w->userId(),
            'userName' => $resolveUser($w->userId()),
            'minutes' => $w->minutes(),
            'description' => $w->description(),
            'date' => $w->date()->format('Y-m-d'),
            'createdAt' => $w->createdAt()->format('c'),
        ], $task->worklogs());

        $assigneeName = null;
        if ($task->assigneeId() !== null) {
            $assigneeUser = $this->userRepository->findById(new \App\IdentityAccess\Domain\Model\UserId($task->assigneeId()));
            $assigneeName = $assigneeUser ? $assigneeUser->username() : null;
        }

        $clientName = null;
        if ($task->clientId() !== null) {
            $client = $this->clientRepository->findById(new \App\ClientManagement\Domain\Model\ClientId($task->clientId()));
            $clientName = $client ? $client->name() : null;
        }

        $parentTaskName = null;
        $subtasks = [];
        $totalTimeSpent = $task->totalTimeSpent();

        if ($task->parentTaskId() !== null) {
            $parentTask = $this->taskRepository->findById(new TaskId($task->parentTaskId()));
            $parentTaskName = $parentTask ? $parentTask->title() : null;
        } else {
            $childTasks = $this->taskRepository->findByParent($task->id());
            foreach ($childTasks as $child) {
                $totalTimeSpent += $child->totalTimeSpent();
                $subtasks[] = [
                    'id' => $child->id()->value(),
                    'title' => $child->title(),
                    'totalTimeSpent' => $child->totalTimeSpent(),
                ];
            }
        }

        return TaskDTO::fromArray([
            'id' => $task->id()->value(),
            'title' => $task->title(),
            'description' => $task->description()->value(),
            'creatorId' => $task->creatorId(),
            'assigneeId' => $task->assigneeId(),
            'assigneeName' => $assigneeName,
            'clientId' => $task->clientId(),
            'clientName' => $clientName,
            'stageId' => $task->stageId(),
            'stageName' => $stageName,
            'position' => $task->position(),
            'parentTaskId' => $task->parentTaskId(),
            'parentTaskName' => $parentTaskName,
            'totalTimeSpent' => $totalTimeSpent,
            'status' => $task->status(),
            'comments' => $comments,
            'worklogs' => $worklogs,
            'subtasks' => $subtasks,
            'createdAt' => $task->createdAt()->format('c'),
            'updatedAt' => $task->updatedAt()->format('c'),
        ]);
    }
}

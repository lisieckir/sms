<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskManagement\Application\Command;

use App\TaskManagement\Application\Command\AssignTask\AssignTaskCommand;
use App\TaskManagement\Application\Command\AssignTask\AssignTaskHandler;
use App\TaskManagement\Domain\Model\Task;
use App\TaskManagement\Domain\Model\TaskDescription;
use App\TaskManagement\Domain\Model\TaskId;
use App\TaskManagement\Domain\Event\TaskAssigned;
use App\Tests\InMemory\InMemoryTaskRepository;
use App\Tests\InMemory\SpyTaskEventProjector;
use PHPUnit\Framework\TestCase;

class AssignTaskHandlerTest extends TestCase
{
    private InMemoryTaskRepository $taskRepository;
    private SpyTaskEventProjector $eventProjector;
    private AssignTaskHandler $handler;

    protected function setUp(): void
    {
        $this->taskRepository = new InMemoryTaskRepository();
        $this->eventProjector = new SpyTaskEventProjector();
        $this->handler = new AssignTaskHandler(
            $this->taskRepository,
            $this->eventProjector,
        );
    }

    public function testAssignTask(): void
    {
        $task = $this->createTask();
        $taskId = $task->id()->value();

        $this->handler->__invoke(new AssignTaskCommand(
            taskId: $taskId,
            assigneeId: 'user-42',
        ));

        $updated = $this->taskRepository->findById(new TaskId($taskId));
        $this->assertSame('user-42', $updated->assigneeId());
    }

    public function testUnassignTask(): void
    {
        $task = $this->createTask('assignee-1');
        $taskId = $task->id()->value();

        $this->handler->__invoke(new AssignTaskCommand(
            taskId: $taskId,
            assigneeId: null,
        ));

        $updated = $this->taskRepository->findById(new TaskId($taskId));
        $this->assertNull($updated->assigneeId());
    }

    public function testAssignTaskNotFoundThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Task not found');

        $this->handler->__invoke(new AssignTaskCommand(
            taskId: '00000000-0000-0000-0000-000000000000',
            assigneeId: 'user-1',
        ));
    }

    public function testAssignTaskProjectsEvent(): void
    {
        $task = $this->createTask();

        $this->handler->__invoke(new AssignTaskCommand(
            taskId: $task->id()->value(),
            assigneeId: 'user-42',
        ));

        $this->assertCount(1, $this->eventProjector->projectedEvents);
        $this->assertInstanceOf(TaskAssigned::class, $this->eventProjector->projectedEvents[0]);
    }

    private function createTask(?string $assigneeId = null): Task
    {
        $task = Task::create(
            TaskId::generate(),
            'Test Task',
            new TaskDescription('Desc'),
            'creator-1',
            'stage-1',
            0,
            $assigneeId,
        );
        $task->releaseEvents();
        $this->taskRepository->save($task);
        return $task;
    }
}

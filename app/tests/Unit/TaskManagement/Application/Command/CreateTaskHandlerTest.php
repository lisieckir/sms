<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskManagement\Application\Command;

use App\TaskManagement\Application\Command\CreateTask\CreateTaskCommand;
use App\TaskManagement\Application\Command\CreateTask\CreateTaskHandler;
use App\TaskManagement\Domain\Model\TaskDescription;
use App\TaskManagement\Domain\Event\TaskCreated;
use App\Tests\InMemory\InMemoryTaskRepository;
use App\Tests\InMemory\SpyTaskEventProjector;
use PHPUnit\Framework\TestCase;

class CreateTaskHandlerTest extends TestCase
{
    private InMemoryTaskRepository $taskRepository;
    private SpyTaskEventProjector $eventProjector;
    private CreateTaskHandler $handler;

    protected function setUp(): void
    {
        $this->taskRepository = new InMemoryTaskRepository();
        $this->eventProjector = new SpyTaskEventProjector();
        $this->handler = new CreateTaskHandler(
            $this->taskRepository,
            $this->eventProjector,
        );
    }

    public function testCreateTask(): void
    {
        $taskId = $this->handler->__invoke(new CreateTaskCommand(
            title: 'Test Task',
            description: 'A test task description',
            creatorId: 'user-1',
            stageId: 'stage-1',
            position: 0,
        ));

        $this->assertNotEmpty($taskId);

        $task = $this->taskRepository->findById(
            new \App\TaskManagement\Domain\Model\TaskId($taskId)
        );
        $this->assertNotNull($task);
        $this->assertSame('Test Task', $task->title());
        $this->assertSame('A test task description', $task->description()->value());
        $this->assertSame('user-1', $task->creatorId());
        $this->assertSame('stage-1', $task->stageId());
        $this->assertSame(0, $task->position());
        $this->assertNull($task->assigneeId());
        $this->assertNull($task->clientId());
        $this->assertNull($task->parentTaskId());
    }

    public function testCreateTaskWithAllOptionalFields(): void
    {
        $taskId = $this->handler->__invoke(new CreateTaskCommand(
            title: 'Full Task',
            description: 'Desc',
            creatorId: 'user-1',
            stageId: 'stage-1',
            position: 2,
            assigneeId: 'user-2',
            clientId: 'client-1',
            parentTaskId: 'parent-1',
        ));

        $task = $this->taskRepository->findById(
            new \App\TaskManagement\Domain\Model\TaskId($taskId)
        );
        $this->assertSame('user-2', $task->assigneeId());
        $this->assertSame('client-1', $task->clientId());
        $this->assertSame('parent-1', $task->parentTaskId());
    }

    public function testCreateTaskProjectsEvent(): void
    {
        $this->handler->__invoke(new CreateTaskCommand(
            title: 'Test',
            description: 'Desc',
            creatorId: 'user-1',
            stageId: 'stage-1',
            position: 0,
        ));

        $this->assertCount(1, $this->eventProjector->projectedEvents);
        $this->assertInstanceOf(TaskCreated::class, $this->eventProjector->projectedEvents[0]);
    }
}

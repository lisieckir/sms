<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskManagement\Application\Command;

use App\TaskManagement\Application\Command\AssignClient\AssignClientCommand;
use App\TaskManagement\Application\Command\AssignClient\AssignClientHandler;
use App\TaskManagement\Domain\Model\Task;
use App\TaskManagement\Domain\Model\TaskDescription;
use App\TaskManagement\Domain\Model\TaskId;
use App\TaskManagement\Domain\Event\TaskClientChanged;
use App\Tests\InMemory\InMemoryTaskRepository;
use App\Tests\InMemory\SpyTaskEventProjector;
use PHPUnit\Framework\TestCase;

class AssignClientHandlerTest extends TestCase
{
    private InMemoryTaskRepository $taskRepository;
    private SpyTaskEventProjector $eventProjector;
    private AssignClientHandler $handler;

    protected function setUp(): void
    {
        $this->taskRepository = new InMemoryTaskRepository();
        $this->eventProjector = new SpyTaskEventProjector();
        $this->handler = new AssignClientHandler(
            $this->taskRepository,
            $this->eventProjector,
        );
    }

    public function testAssignClient(): void
    {
        $task = $this->createTask();
        $taskId = $task->id()->value();

        $this->handler->__invoke(new AssignClientCommand(
            taskId: $taskId,
            clientId: 'client-42',
        ));

        $updated = $this->taskRepository->findById(new TaskId($taskId));
        $this->assertSame('client-42', $updated->clientId());
    }

    public function testUnassignClient(): void
    {
        $task = $this->createTask('client-1');
        $taskId = $task->id()->value();

        $this->handler->__invoke(new AssignClientCommand(
            taskId: $taskId,
            clientId: null,
        ));

        $updated = $this->taskRepository->findById(new TaskId($taskId));
        $this->assertNull($updated->clientId());
    }

    public function testAssignClientNotFoundThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Task not found');

        $this->handler->__invoke(new AssignClientCommand(
            taskId: '00000000-0000-0000-0000-000000000000',
            clientId: 'client-1',
        ));
    }

    public function testAssignClientProjectsEvent(): void
    {
        $task = $this->createTask();

        $this->handler->__invoke(new AssignClientCommand(
            taskId: $task->id()->value(),
            clientId: 'client-99',
        ));

        $this->assertCount(1, $this->eventProjector->projectedEvents);
        $this->assertInstanceOf(TaskClientChanged::class, $this->eventProjector->projectedEvents[0]);
    }

    private function createTask(?string $clientId = null): Task
    {
        $task = Task::create(
            TaskId::generate(),
            'Test Task',
            new TaskDescription('Desc'),
            'creator-1',
            'stage-1',
            0,
            null,
            $clientId,
        );
        $task->releaseEvents();
        $this->taskRepository->save($task);
        return $task;
    }
}

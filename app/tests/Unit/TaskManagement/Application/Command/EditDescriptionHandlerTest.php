<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskManagement\Application\Command;

use App\TaskManagement\Application\Command\EditDescription\EditDescriptionCommand;
use App\TaskManagement\Application\Command\EditDescription\EditDescriptionHandler;
use App\TaskManagement\Domain\Model\Task;
use App\TaskManagement\Domain\Model\TaskDescription;
use App\TaskManagement\Domain\Model\TaskId;
use App\TaskManagement\Domain\Event\TaskDescriptionChanged;
use App\Tests\InMemory\InMemoryTaskRepository;
use App\Tests\InMemory\SpyTaskEventProjector;
use PHPUnit\Framework\TestCase;

class EditDescriptionHandlerTest extends TestCase
{
    private InMemoryTaskRepository $taskRepository;
    private SpyTaskEventProjector $eventProjector;
    private EditDescriptionHandler $handler;

    protected function setUp(): void
    {
        $this->taskRepository = new InMemoryTaskRepository();
        $this->eventProjector = new SpyTaskEventProjector();
        $this->handler = new EditDescriptionHandler(
            $this->taskRepository,
            $this->eventProjector,
        );
    }

    public function testEditDescription(): void
    {
        $task = $this->createTask();
        $taskId = $task->id()->value();

        $this->handler->__invoke(new EditDescriptionCommand(
            taskId: $taskId,
            description: 'Updated description',
        ));

        $updated = $this->taskRepository->findById(new TaskId($taskId));
        $this->assertSame('Updated description', $updated->description()->value());
    }

    public function testEditDescriptionNotFoundThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Task not found');

        $this->handler->__invoke(new EditDescriptionCommand(
            taskId: '00000000-0000-0000-0000-000000000000',
            description: 'test',
        ));
    }

    public function testEditDescriptionProjectsEvent(): void
    {
        $task = $this->createTask();

        $this->handler->__invoke(new EditDescriptionCommand(
            taskId: $task->id()->value(),
            description: 'New desc',
        ));

        $this->assertCount(1, $this->eventProjector->projectedEvents);
        $this->assertInstanceOf(TaskDescriptionChanged::class, $this->eventProjector->projectedEvents[0]);
    }

    private function createTask(): Task
    {
        $task = Task::create(
            TaskId::generate(),
            'Test',
            new TaskDescription('Original desc'),
            'user-1',
            'stage-1',
            0,
        );
        $task->releaseEvents();
        $this->taskRepository->save($task);
        return $task;
    }
}

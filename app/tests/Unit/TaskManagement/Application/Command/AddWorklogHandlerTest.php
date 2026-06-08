<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskManagement\Application\Command;

use App\TaskManagement\Application\Command\AddWorklog\AddWorklogCommand;
use App\TaskManagement\Application\Command\AddWorklog\AddWorklogHandler;
use App\TaskManagement\Domain\Model\Task;
use App\TaskManagement\Domain\Model\TaskDescription;
use App\TaskManagement\Domain\Model\TaskId;
use App\TaskManagement\Domain\Event\WorklogAdded;
use App\Tests\InMemory\InMemoryTaskRepository;
use App\Tests\InMemory\SpyTaskEventProjector;
use PHPUnit\Framework\TestCase;

class AddWorklogHandlerTest extends TestCase
{
    private InMemoryTaskRepository $taskRepository;
    private SpyTaskEventProjector $eventProjector;
    private AddWorklogHandler $handler;

    protected function setUp(): void
    {
        $this->taskRepository = new InMemoryTaskRepository();
        $this->eventProjector = new SpyTaskEventProjector();
        $this->handler = new AddWorklogHandler(
            $this->taskRepository,
            $this->eventProjector,
        );
    }

    public function testAddWorklog(): void
    {
        $task = $this->createTask();

        $this->handler->__invoke(new AddWorklogCommand(
            taskId: $task->id()->value(),
            userId: 'user-1',
            minutes: 60,
            description: 'Worked on feature',
        ));

        $updated = $this->taskRepository->findById($task->id());
        $this->assertCount(1, $updated->worklogs());
        $this->assertSame(60, $updated->totalTimeSpent());
    }

    public function testAddWorklogAccumulatesTime(): void
    {
        $task = $this->createTask();

        $this->handler->__invoke(new AddWorklogCommand(
            taskId: $task->id()->value(),
            userId: 'user-1',
            minutes: 30,
            description: 'First',
        ));
        $this->handler->__invoke(new AddWorklogCommand(
            taskId: $task->id()->value(),
            userId: 'user-2',
            minutes: 45,
            description: 'Second',
        ));

        $updated = $this->taskRepository->findById($task->id());
        $this->assertSame(75, $updated->totalTimeSpent());
    }

    public function testAddWorklogProjectsEvents(): void
    {
        $task = $this->createTask();

        $this->handler->__invoke(new AddWorklogCommand(
            taskId: $task->id()->value(),
            userId: 'user-1',
            minutes: 30,
            description: 'Test',
        ));

        $this->assertCount(1, $this->eventProjector->projectedEvents);
        $this->assertInstanceOf(WorklogAdded::class, $this->eventProjector->projectedEvents[0]);
    }

    public function testAddWorklogTaskNotFoundThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->handler->__invoke(new AddWorklogCommand(
            taskId: 'bad-id',
            userId: 'user-1',
            minutes: 30,
            description: 'test',
        ));
    }

    private function createTask(): Task
    {
        $task = Task::create(
            TaskId::generate(),
            'Test',
            new TaskDescription('Desc'),
            'user-1',
            'stage-1',
            0,
        );
        $task->releaseEvents();
        $this->taskRepository->save($task);
        return $task;
    }
}

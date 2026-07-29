<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskManagement\Application\Command;

use App\TaskManagement\Application\Command\AddComment\AddCommentCommand;
use App\TaskManagement\Application\Command\AddComment\AddCommentHandler;
use App\TaskManagement\Domain\Model\Task;
use App\TaskManagement\Domain\Model\TaskDescription;
use App\TaskManagement\Domain\Model\TaskId;
use App\TaskManagement\Domain\Event\CommentAdded;
use App\Tests\InMemory\InMemoryTaskRepository;
use App\Tests\InMemory\SpyTaskEventProjector;
use PHPUnit\Framework\TestCase;

class AddCommentHandlerTest extends TestCase
{
    private InMemoryTaskRepository $taskRepository;
    private SpyTaskEventProjector $eventProjector;
    private AddCommentHandler $handler;

    protected function setUp(): void
    {
        $this->taskRepository = new InMemoryTaskRepository();
        $this->eventProjector = new SpyTaskEventProjector();
        $this->handler = new AddCommentHandler(
            $this->taskRepository,
            $this->eventProjector,
        );
    }

    public function testAddComment(): void
    {
        $task = $this->createTask();

        $this->handler->__invoke(new AddCommentCommand(
            taskId: $task->id()->value(),
            userId: 'user-1',
            content: 'A new comment',
        ));

        $updated = $this->taskRepository->findById($task->id());
        $this->assertCount(1, $updated->comments());
        $this->assertSame('user-1', $updated->comments()[0]->userId());
        $this->assertSame('A new comment', $updated->comments()[0]->content());
    }

    public function testAddCommentWithMultilineContent(): void
    {
        $task = $this->createTask();
        $multiline = "First line\nSecond line\n\nThird paragraph";

        $this->handler->__invoke(new AddCommentCommand(
            taskId: $task->id()->value(),
            userId: 'user-1',
            content: $multiline,
        ));

        $updated = $this->taskRepository->findById($task->id());
        $this->assertCount(1, $updated->comments());
        $this->assertSame($multiline, $updated->comments()[0]->content());
        $this->assertStringContainsString("\n", $updated->comments()[0]->content());
    }

    public function testAddCommentProjectsEvent(): void
    {
        $task = $this->createTask();

        $this->handler->__invoke(new AddCommentCommand(
            taskId: $task->id()->value(),
            userId: 'user-1',
            content: 'Nice work',
        ));

        $this->assertCount(1, $this->eventProjector->projectedEvents);
        $this->assertInstanceOf(CommentAdded::class, $this->eventProjector->projectedEvents[0]);
    }

    public function testAddCommentTaskNotFoundThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->handler->__invoke(new AddCommentCommand(
            taskId: 'bad-id',
            userId: 'user-1',
            content: 'test',
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

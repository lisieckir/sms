<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskManagement\Application\Command;

use App\TaskManagement\Application\Command\DeleteComment\DeleteCommentCommand;
use App\TaskManagement\Application\Command\DeleteComment\DeleteCommentHandler;
use App\TaskManagement\Domain\Model\Task;
use App\TaskManagement\Domain\Model\TaskDescription;
use App\TaskManagement\Domain\Model\TaskId;
use App\TaskManagement\Domain\Event\CommentRemoved;
use App\Tests\InMemory\InMemoryTaskRepository;
use App\Tests\InMemory\SpyTaskEventProjector;
use PHPUnit\Framework\TestCase;

class DeleteCommentHandlerTest extends TestCase
{
    private InMemoryTaskRepository $taskRepository;
    private SpyTaskEventProjector $eventProjector;
    private DeleteCommentHandler $handler;

    protected function setUp(): void
    {
        $this->taskRepository = new InMemoryTaskRepository();
        $this->eventProjector = new SpyTaskEventProjector();
        $this->handler = new DeleteCommentHandler(
            $this->taskRepository,
            $this->eventProjector,
        );
    }

    public function testDeleteComment(): void
    {
        $task = $this->createTaskWithComment();
        $commentId = $task->comments()[0]->id();

        $this->handler->__invoke(new DeleteCommentCommand(
            taskId: $task->id()->value(),
            commentId: $commentId,
        ));

        $updated = $this->taskRepository->findById($task->id());
        $this->assertCount(0, $updated->comments());
    }

    public function testDeleteCommentProjectsEvent(): void
    {
        $task = $this->createTaskWithComment();
        $commentId = $task->comments()[0]->id();

        $this->handler->__invoke(new DeleteCommentCommand(
            taskId: $task->id()->value(),
            commentId: $commentId,
        ));

        $this->assertCount(1, $this->eventProjector->projectedEvents);
        $this->assertInstanceOf(CommentRemoved::class, $this->eventProjector->projectedEvents[0]);
    }

    public function testDeleteCommentTaskNotFoundThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->handler->__invoke(new DeleteCommentCommand(
            taskId: 'bad-id',
            commentId: 'comment-1',
        ));
    }

    public function testDeleteCommentNotFoundThrowsException(): void
    {
        $task = $this->createTaskWithComment();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Comment not found');

        $this->handler->__invoke(new DeleteCommentCommand(
            taskId: $task->id()->value(),
            commentId: 'nonexistent',
        ));
    }

    private function createTaskWithComment(): Task
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
        $task->addComment('user-1', 'Comment to delete');
        $task->releaseEvents();
        $this->taskRepository->save($task);
        return $task;
    }
}

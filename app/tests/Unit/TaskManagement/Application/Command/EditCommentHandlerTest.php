<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskManagement\Application\Command;

use App\TaskManagement\Application\Command\EditComment\EditCommentCommand;
use App\TaskManagement\Application\Command\EditComment\EditCommentHandler;
use App\TaskManagement\Domain\Model\Task;
use App\TaskManagement\Domain\Model\TaskDescription;
use App\TaskManagement\Domain\Model\TaskId;
use App\TaskManagement\Domain\Event\CommentEdited;
use App\Tests\InMemory\InMemoryTaskRepository;
use App\Tests\InMemory\SpyTaskEventProjector;
use PHPUnit\Framework\TestCase;

class EditCommentHandlerTest extends TestCase
{
    private InMemoryTaskRepository $taskRepository;
    private SpyTaskEventProjector $eventProjector;
    private EditCommentHandler $handler;

    protected function setUp(): void
    {
        $this->taskRepository = new InMemoryTaskRepository();
        $this->eventProjector = new SpyTaskEventProjector();
        $this->handler = new EditCommentHandler(
            $this->taskRepository,
            $this->eventProjector,
        );
    }

    public function testEditComment(): void
    {
        $task = $this->createTaskWithComment();
        $commentId = $task->comments()[0]->id();

        $this->handler->__invoke(new EditCommentCommand(
            taskId: $task->id()->value(),
            commentId: $commentId,
            content: 'Updated content',
        ));

        $updated = $this->taskRepository->findById($task->id());
        $this->assertSame('Updated content', $updated->comments()[0]->content());
        $this->assertNotNull($updated->comments()[0]->editedAt());
    }

    public function testEditCommentProjectsEvent(): void
    {
        $task = $this->createTaskWithComment();
        $commentId = $task->comments()[0]->id();

        $this->handler->__invoke(new EditCommentCommand(
            taskId: $task->id()->value(),
            commentId: $commentId,
            content: 'Updated',
        ));

        $this->assertCount(1, $this->eventProjector->projectedEvents);
        $this->assertInstanceOf(CommentEdited::class, $this->eventProjector->projectedEvents[0]);
    }

    public function testEditCommentTaskNotFoundThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->handler->__invoke(new EditCommentCommand(
            taskId: 'bad-id',
            commentId: 'comment-1',
            content: 'test',
        ));
    }

    public function testEditCommentNotFoundThrowsException(): void
    {
        $task = $this->createTaskWithComment();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Comment not found');

        $this->handler->__invoke(new EditCommentCommand(
            taskId: $task->id()->value(),
            commentId: 'nonexistent',
            content: 'test',
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
        $task->addComment('user-1', 'Original comment');
        $task->releaseEvents();
        $this->taskRepository->save($task);
        return $task;
    }
}

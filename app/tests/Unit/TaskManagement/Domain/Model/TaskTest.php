<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskManagement\Domain\Model;

use App\TaskManagement\Domain\Model\Task;
use App\TaskManagement\Domain\Model\TaskDescription;
use App\TaskManagement\Domain\Model\TaskId;
use App\TaskManagement\Domain\Event\TaskCreated;
use App\TaskManagement\Domain\Event\TaskMoved;
use App\TaskManagement\Domain\Event\TaskAssigned;
use App\TaskManagement\Domain\Event\TaskDescriptionChanged;
use App\TaskManagement\Domain\Event\CommentAdded;
use App\TaskManagement\Domain\Event\WorklogAdded;
use PHPUnit\Framework\TestCase;

class TaskTest extends TestCase
{
    public function testCreate(): void
    {
        $task = $this->createTask();

        $this->assertInstanceOf(TaskId::class, $task->id());
        $this->assertSame('Test Task', $task->title());
        $this->assertSame('A description', $task->description()->value());
        $this->assertSame('creator-1', $task->creatorId());
        $this->assertSame('stage-1', $task->stageId());
        $this->assertSame(0, $task->position());
        $this->assertNull($task->assigneeId());
        $this->assertNull($task->clientId());
        $this->assertNull($task->parentTaskId());
        $this->assertTrue($task->isActive());
        $this->assertSame(0, $task->totalTimeSpent());

        $events = $task->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(TaskCreated::class, $events[0]);
    }

    public function testCreateWithOptionalFields(): void
    {
        $id = TaskId::generate();
        $task = Task::create(
            $id,
            'Task',
            new TaskDescription('Desc'),
            'creator-1',
            'stage-1',
            0,
            'assignee-1',
            'client-1',
            'parent-1',
        );

        $this->assertSame('assignee-1', $task->assigneeId());
        $this->assertSame('client-1', $task->clientId());
        $this->assertSame('parent-1', $task->parentTaskId());
    }

    public function testMoveToStage(): void
    {
        $task = $this->createTaskAndClearEvents();

        $task->moveToStage('stage-2', 1);

        $this->assertSame('stage-2', $task->stageId());
        $this->assertSame(1, $task->position());

        $events = $task->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(TaskMoved::class, $events[0]);
    }

    public function testAssignTo(): void
    {
        $task = $this->createTaskAndClearEvents();

        $task->assignTo('user-42');

        $this->assertSame('user-42', $task->assigneeId());

        $events = $task->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(TaskAssigned::class, $events[0]);
    }

    public function testAssignToNull(): void
    {
        $task = $this->createTaskAndClearEvents();
        $task->assignTo('user-42');
        $task->releaseEvents();

        $task->assignTo(null);

        $this->assertNull($task->assigneeId());
    }

    public function testChangeDescription(): void
    {
        $task = $this->createTaskAndClearEvents();
        $newDesc = new TaskDescription('New description');

        $task->changeDescription($newDesc);

        $this->assertSame('New description', $task->description()->value());

        $events = $task->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(TaskDescriptionChanged::class, $events[0]);
    }

    public function testAddComment(): void
    {
        $task = $this->createTaskAndClearEvents();

        $task->addComment('user-1', 'First comment');

        $this->assertCount(1, $task->comments());
        $this->assertSame('user-1', $task->comments()[0]->userId());
        $this->assertSame('First comment', $task->comments()[0]->content());

        $events = $task->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(CommentAdded::class, $events[0]);
    }

    public function testAddCommentEnforcesMax50Limit(): void
    {
        $task = $this->createTaskAndClearEvents();

        for ($i = 0; $i < 50; $i++) {
            $task->addComment('user-1', "Comment $i");
        }
        $task->releaseEvents();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Maximum of 50 comments per task reached');
        $task->addComment('user-1', 'One more');
    }

    public function testAddWorklog(): void
    {
        $task = $this->createTaskAndClearEvents();

        $task->addWorklog('user-1', 30, 'Fixed bug');

        $this->assertCount(1, $task->worklogs());
        $this->assertSame(30, $task->totalTimeSpent());

        $events = $task->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(WorklogAdded::class, $events[0]);
    }

    public function testWorklogAccumulatesTotalTime(): void
    {
        $task = $this->createTaskAndClearEvents();

        $task->addWorklog('user-1', 30, 'First');
        $task->releaseEvents();

        $task->addWorklog('user-2', 45, 'Second');

        $this->assertSame(75, $task->totalTimeSpent());
    }

    public function testArchive(): void
    {
        $task = $this->createTaskAndClearEvents();

        $task->archive();

        $this->assertSame('archived', $task->status());
        $this->assertFalse($task->isActive());
        $this->assertCount(0, $task->releaseEvents());
    }

    public function testGetters(): void
    {
        $task = $this->createTask();
        $task->releaseEvents();

        $this->assertNotNull($task->createdAt());
        $this->assertNotNull($task->updatedAt());
        $this->assertSame('active', $task->status());
    }

    private function createTask(): Task
    {
        return Task::create(
            TaskId::generate(),
            'Test Task',
            new TaskDescription('A description'),
            'creator-1',
            'stage-1',
            0,
        );
    }

    private function createTaskAndClearEvents(): Task
    {
        $task = $this->createTask();
        $task->releaseEvents();
        return $task;
    }
}

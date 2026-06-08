<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskManagement\Application\Command;

use App\TaskManagement\Application\Command\MoveTask\MoveTaskCommand;
use App\TaskManagement\Application\Command\MoveTask\MoveTaskHandler;
use App\TaskManagement\Domain\Model\Task;
use App\TaskManagement\Domain\Model\TaskDescription;
use App\TaskManagement\Domain\Model\TaskId;
use App\TaskManagement\Domain\Event\TaskMoved;
use App\Tests\InMemory\InMemoryTaskRepository;
use App\Tests\InMemory\InMemoryWorkflowRepository;
use App\Tests\InMemory\SpyTaskEventProjector;
use App\BoardManagement\Domain\Model\Workflow;
use App\BoardManagement\Domain\Model\WorkflowId;
use PHPUnit\Framework\TestCase;

class MoveTaskHandlerTest extends TestCase
{
    private InMemoryTaskRepository $taskRepository;
    private SpyTaskEventProjector $eventProjector;
    private InMemoryWorkflowRepository $workflowRepository;
    private MoveTaskHandler $handler;

    private string $stage1Id;
    private string $stage2Id;

    protected function setUp(): void
    {
        $this->taskRepository = new InMemoryTaskRepository();
        $this->eventProjector = new SpyTaskEventProjector();
        $this->workflowRepository = new InMemoryWorkflowRepository();
        $workflow = Workflow::createDefault(WorkflowId::generate());
        $stages = $workflow->stages();
        $this->stage1Id = $stages[0]->id();
        $this->stage2Id = $stages[1]->id();
        $this->workflowRepository->save($workflow);
        $this->handler = new MoveTaskHandler(
            $this->taskRepository,
            $this->workflowRepository,
            $this->eventProjector,
        );
    }

    public function testMoveTask(): void
    {
        $task = $this->createTask();
        $taskId = $task->id()->value();

        $this->handler->__invoke(new MoveTaskCommand(
            taskId: $taskId,
            stageId: $this->stage2Id,
            position: 1,
        ));

        $updated = $this->taskRepository->findById(new TaskId($taskId));
        $this->assertSame($this->stage2Id, $updated->stageId());
        $this->assertSame(1, $updated->position());
    }

    public function testMoveTaskNotFoundThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Task not found');

        $this->handler->__invoke(new MoveTaskCommand(
            taskId: '00000000-0000-0000-0000-000000000000',
            stageId: $this->stage2Id,
            position: 0,
        ));
    }

    public function testMoveTaskProjectsEvent(): void
    {
        $task = $this->createTask();

        $this->handler->__invoke(new MoveTaskCommand(
            taskId: $task->id()->value(),
            stageId: $this->stage2Id,
            position: 1,
        ));

        $this->assertCount(1, $this->eventProjector->projectedEvents);
        $this->assertInstanceOf(TaskMoved::class, $this->eventProjector->projectedEvents[0]);
    }

    private function createTask(): Task
    {
        $task = Task::create(
            TaskId::generate(),
            'Test Task',
            new TaskDescription('Desc'),
            'creator-1',
            $this->stage1Id,
            0,
        );
        $task->releaseEvents();
        $this->taskRepository->save($task);
        return $task;
    }
}

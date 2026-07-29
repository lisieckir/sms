<?php

declare(strict_types=1);

namespace App\Tests\Unit\BoardManagement\Application\Query;

use App\BoardManagement\Application\DTO\StageDTO;
use App\BoardManagement\Application\Query\GetBoardHandler;
use App\BoardManagement\Application\Query\GetBoardQuery;
use App\BoardManagement\Domain\Model\Workflow;
use App\BoardManagement\Domain\Model\WorkflowId;
use App\BoardManagement\Domain\Model\Stage;
use App\TaskManagement\Domain\Model\Task;
use App\TaskManagement\Domain\Model\TaskDescription;
use App\TaskManagement\Domain\Model\TaskId;
use App\Tests\InMemory\InMemoryTaskRepository;
use App\Tests\InMemory\InMemoryWorkflowRepository;
use PHPUnit\Framework\TestCase;

class GetBoardHandlerTest extends TestCase
{
    private InMemoryWorkflowRepository $workflowRepository;
    private InMemoryTaskRepository $taskRepository;
    private GetBoardHandler $handler;

    protected function setUp(): void
    {
        $this->workflowRepository = new InMemoryWorkflowRepository();
        $this->taskRepository = new InMemoryTaskRepository();
        $this->handler = new GetBoardHandler(
            $this->workflowRepository,
            $this->taskRepository,
        );
    }

    public function testGetBoardReturnsStagesWithTasks(): void
    {
        $stageIds = $this->createWorkflowWithStages(['Backlog', 'In Progress', 'Done']);

        $this->createTask($stageIds[0], 0);
        $this->createTask($stageIds[1], 0);

        $stages = $this->handler->__invoke(new GetBoardQuery());

        $this->assertCount(3, $stages);
        $this->assertContainsOnlyInstancesOf(StageDTO::class, $stages);
        $this->assertCount(1, $stages[0]->tasks());
        $this->assertCount(1, $stages[1]->tasks());
        $this->assertCount(0, $stages[2]->tasks());
    }

    public function testGetBoardEmptyWhenNoWorkflow(): void
    {
        $stages = $this->handler->__invoke(new GetBoardQuery());
        $this->assertEmpty($stages);
    }

    public function testGetBoardSortsTasksByPosition(): void
    {
        $stageIds = $this->createWorkflowWithStages(['Backlog']);

        $this->createTask($stageIds[0], 1, 'Second');
        $this->createTask($stageIds[0], 0, 'First');

        $stages = $this->handler->__invoke(new GetBoardQuery());

        $this->assertSame('First', $stages[0]->tasks()[0]['title']);
        $this->assertSame('Second', $stages[0]->tasks()[1]['title']);
    }

    public function testGetBoardFiltersByClient(): void
    {
        $stageIds = $this->createWorkflowWithStages(['Backlog']);

        $this->createTask($stageIds[0], 0, 'Task A', 'client-1');
        $this->createTask($stageIds[0], 1, 'Task B', 'client-2');

        $stages = $this->handler->__invoke(new GetBoardQuery(clientId: 'client-1'));

        $this->assertCount(1, $stages[0]->tasks());
        $this->assertSame('Task A', $stages[0]->tasks()[0]['title']);
    }

    public function testGetBoardFiltersByAssignee(): void
    {
        $stageIds = $this->createWorkflowWithStages(['Backlog']);

        $this->createTask($stageIds[0], 0, 'Task A', null, 'user-1');
        $this->createTask($stageIds[0], 1, 'Task B', null, 'user-2');

        $stages = $this->handler->__invoke(new GetBoardQuery(assigneeId: 'user-1'));

        $this->assertCount(1, $stages[0]->tasks());
        $this->assertSame('Task A', $stages[0]->tasks()[0]['title']);
    }

    public function testGetBoardStagesAreSortedByPosition(): void
    {
        $workflow = Workflow::create(WorkflowId::generate(), 'Default', true);
        $workflow->addStage('Done', 2);
        $workflow->addStage('Backlog', 0);
        $workflow->addStage('In Progress', 1);
        $workflow->releaseEvents();
        $this->workflowRepository->save($workflow);

        $stages = $this->handler->__invoke(new GetBoardQuery());

        $this->assertSame('Backlog', $stages[0]->name());
        $this->assertSame('In Progress', $stages[1]->name());
        $this->assertSame('Done', $stages[2]->name());
    }

    private function createWorkflowWithStages(array $stageNames): array
    {
        $workflow = Workflow::create(WorkflowId::generate(), 'Default', true);
        foreach ($stageNames as $i => $name) {
            $workflow->addStage($name, $i);
        }
        $workflow->releaseEvents();
        $this->workflowRepository->save($workflow);

        $workflow = $this->workflowRepository->findDefault();
        return array_map(fn($s) => $s->id(), $workflow->sortedStages());
    }

    private function createTask(string $stageId, int $position, string $title = 'Task', ?string $clientId = null, ?string $assigneeId = null): Task
    {
        $task = Task::create(
            TaskId::generate(),
            $title,
            new TaskDescription('Desc'),
            'creator-1',
            $stageId,
            $position,
            $assigneeId,
            $clientId,
        );
        $task->releaseEvents();
        $this->taskRepository->save($task);
        return $task;
    }
}

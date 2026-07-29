<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskManagement\Application\Query;

use App\Core\UI\Twig\MarkdownConverter;
use App\TaskManagement\Application\DTO\TaskDTO;
use App\TaskManagement\Application\Query\ListTasksByStageHandler;
use App\TaskManagement\Application\Query\ListTasksByStageQuery;
use App\TaskManagement\Domain\Model\Task;
use App\TaskManagement\Domain\Model\TaskDescription;
use App\TaskManagement\Domain\Model\TaskId;
use App\Tests\InMemory\InMemoryTaskRepository;
use PHPUnit\Framework\TestCase;

class ListTasksByStageHandlerTest extends TestCase
{
    private InMemoryTaskRepository $taskRepository;
    private MarkdownConverter $markdownConverter;
    private ListTasksByStageHandler $handler;

    protected function setUp(): void
    {
        $this->taskRepository = new InMemoryTaskRepository();
        $this->markdownConverter = new MarkdownConverter();
        $this->handler = new ListTasksByStageHandler(
            $this->taskRepository,
            $this->markdownConverter,
        );
    }

    public function testListTasksReturnsTaskDTOs(): void
    {
        $this->createTask('stage-1', 0);
        $this->createTask('stage-1', 1);

        $dtos = $this->handler->__invoke(new ListTasksByStageQuery('stage-1'));

        $this->assertCount(2, $dtos);
        $this->assertContainsOnlyInstancesOf(TaskDTO::class, $dtos);
    }

    public function testListTasksEmptyForUnknownStage(): void
    {
        $dtos = $this->handler->__invoke(new ListTasksByStageQuery('nonexistent'));
        $this->assertEmpty($dtos);
    }

    public function testListTasksFiltersByStage(): void
    {
        $this->createTask('stage-1', 0);
        $this->createTask('stage-2', 0);

        $dtos = $this->handler->__invoke(new ListTasksByStageQuery('stage-1'));
        $this->assertCount(1, $dtos);
    }

    public function testListTasksRendersDescriptionHtml(): void
    {
        $this->createTask('stage-1', 0, 'Hello **world**');

        $dtos = $this->handler->__invoke(new ListTasksByStageQuery('stage-1'));

        $this->assertStringContainsString('<strong>world</strong>', $dtos[0]->descriptionHtml());
    }

    public function testListTasksDoesNotPopulateNestedData(): void
    {
        $this->createTask('stage-1', 0);

        $dtos = $this->handler->__invoke(new ListTasksByStageQuery('stage-1'));

        $this->assertEmpty($dtos[0]->comments());
        $this->assertEmpty($dtos[0]->worklogs());
        $this->assertNull($dtos[0]->assigneeName());
        $this->assertNull($dtos[0]->clientName());
    }

    private function createTask(string $stageId, int $position, string $description = 'Test task'): Task
    {
        $task = Task::create(
            TaskId::generate(),
            'Task ' . $position,
            new TaskDescription($description),
            'creator-1',
            $stageId,
            $position,
        );
        $task->releaseEvents();
        $this->taskRepository->save($task);
        return $task;
    }
}

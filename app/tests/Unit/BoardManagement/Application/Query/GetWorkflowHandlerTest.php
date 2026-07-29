<?php

declare(strict_types=1);

namespace App\Tests\Unit\BoardManagement\Application\Query;

use App\BoardManagement\Application\Query\GetWorkflowHandler;
use App\BoardManagement\Application\Query\GetWorkflowQuery;
use App\BoardManagement\Domain\Model\Transition;
use App\BoardManagement\Domain\Model\Workflow;
use App\BoardManagement\Domain\Model\WorkflowId;
use App\Tests\InMemory\InMemoryWorkflowRepository;
use PHPUnit\Framework\TestCase;

class GetWorkflowHandlerTest extends TestCase
{
    private InMemoryWorkflowRepository $workflowRepository;
    private GetWorkflowHandler $handler;

    protected function setUp(): void
    {
        $this->workflowRepository = new InMemoryWorkflowRepository();
        $this->handler = new GetWorkflowHandler(
            $this->workflowRepository,
        );
    }

    public function testGetWorkflowReturnsArray(): void
    {
        $workflow = Workflow::create(WorkflowId::generate(), 'Default', true);
        $workflow->addStage('Backlog', 0);
        $workflow->addStage('Done', 1);
        $stages = $workflow->sortedStages();
        $workflow->addTransition($stages[0]->id(), $stages[1]->id());
        $workflow->releaseEvents();
        $this->workflowRepository->save($workflow);

        $result = $this->handler->__invoke(new GetWorkflowQuery());

        $this->assertNotNull($result);
        $this->assertSame('Default', $result['name']);
        $this->assertTrue($result['isDefault']);
        $this->assertCount(2, $result['stages']);
        $this->assertCount(1, $result['transitions']);
        $this->assertSame('Backlog', $result['stages'][0]->name());
    }

    public function testGetWorkflowReturnsNullWhenNoWorkflow(): void
    {
        $result = $this->handler->__invoke(new GetWorkflowQuery());
        $this->assertNull($result);
    }

    public function testGetWorkflowStagesAreSorted(): void
    {
        $workflow = Workflow::create(WorkflowId::generate(), 'Default', true);
        $workflow->addStage('Done', 2);
        $workflow->addStage('Backlog', 0);
        $workflow->addStage('In Progress', 1);
        $workflow->releaseEvents();
        $this->workflowRepository->save($workflow);

        $result = $this->handler->__invoke(new GetWorkflowQuery());

        $this->assertSame('Backlog', $result['stages'][0]->name());
        $this->assertSame('In Progress', $result['stages'][1]->name());
        $this->assertSame('Done', $result['stages'][2]->name());
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\BoardManagement\Application\Command;

use App\BoardManagement\Application\Command\AddStage\AddStageCommand;
use App\BoardManagement\Application\Command\AddStage\AddStageHandler;
use App\BoardManagement\Domain\Model\Workflow;
use App\BoardManagement\Domain\Model\WorkflowId;
use App\Tests\InMemory\InMemoryWorkflowRepository;
use PHPUnit\Framework\TestCase;

class AddStageHandlerTest extends TestCase
{
    private InMemoryWorkflowRepository $workflowRepository;
    private AddStageHandler $handler;
    private string $workflowId;

    protected function setUp(): void
    {
        $this->workflowRepository = new InMemoryWorkflowRepository();
        $workflow = Workflow::create(WorkflowId::generate(), 'Test', false);
        $this->workflowId = $workflow->id()->value();
        $this->workflowRepository->save($workflow);
        $this->handler = new AddStageHandler(
            $this->workflowRepository,
        );
    }

    public function testAddStage(): void
    {
        $this->handler->__invoke(new AddStageCommand(
            workflowId: $this->workflowId,
            name: 'In Progress',
            position: 0,
        ));

        $workflow = $this->workflowRepository->findById(new WorkflowId($this->workflowId));
        $this->assertCount(1, $workflow->stages());
        $this->assertSame('In Progress', $workflow->stages()[0]->name());
    }

    public function testAddStageWorkflowNotFoundThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->handler->__invoke(new AddStageCommand(
            workflowId: '00000000-0000-0000-0000-000000000000',
            name: 'Stage',
            position: 0,
        ));
    }
}

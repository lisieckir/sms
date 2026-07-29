<?php

declare(strict_types=1);

namespace App\Tests\Unit\BoardManagement\Application\Command;

use App\BoardManagement\Application\Command\RemoveStage\RemoveStageCommand;
use App\BoardManagement\Application\Command\RemoveStage\RemoveStageHandler;
use App\BoardManagement\Domain\Model\Workflow;
use App\BoardManagement\Domain\Model\WorkflowId;
use App\Tests\InMemory\InMemoryWorkflowRepository;
use PHPUnit\Framework\TestCase;

class RemoveStageHandlerTest extends TestCase
{
    private InMemoryWorkflowRepository $workflowRepository;
    private RemoveStageHandler $handler;
    private string $workflowId;
    private string $stageId;

    protected function setUp(): void
    {
        $this->workflowRepository = new InMemoryWorkflowRepository();
        $workflow = Workflow::create(WorkflowId::generate(), 'Test', false);
        $workflow->addStage('To Do', 0);
        $workflow->addStage('Done', 1);
        $this->stageId = $workflow->stages()[0]->id();
        $this->workflowId = $workflow->id()->value();
        $this->workflowRepository->save($workflow);
        $this->handler = new RemoveStageHandler(
            $this->workflowRepository,
        );
    }

    public function testRemoveStage(): void
    {
        $this->handler->__invoke(new RemoveStageCommand(
            workflowId: $this->workflowId,
            stageId: $this->stageId,
        ));

        $workflow = $this->workflowRepository->findById(new WorkflowId($this->workflowId));
        $this->assertCount(1, $workflow->stages());
    }

    public function testRemoveStageWorkflowNotFoundThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->handler->__invoke(new RemoveStageCommand(
            workflowId: '00000000-0000-0000-0000-000000000000',
            stageId: $this->stageId,
        ));
    }
}

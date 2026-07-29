<?php

declare(strict_types=1);

namespace App\Tests\Unit\BoardManagement\Application\Command;

use App\BoardManagement\Application\Command\ReorderStage\ReorderStageCommand;
use App\BoardManagement\Application\Command\ReorderStage\ReorderStageHandler;
use App\BoardManagement\Domain\Model\Workflow;
use App\BoardManagement\Domain\Model\WorkflowId;
use App\Tests\InMemory\InMemoryWorkflowRepository;
use PHPUnit\Framework\TestCase;

class ReorderStageHandlerTest extends TestCase
{
    private InMemoryWorkflowRepository $workflowRepository;
    private ReorderStageHandler $handler;
    private string $workflowId;

    protected function setUp(): void
    {
        $this->workflowRepository = new InMemoryWorkflowRepository();
        $workflow = Workflow::create(WorkflowId::generate(), 'Test', false);
        $workflow->addStage('First', 0);
        $workflow->addStage('Second', 1);
        $this->workflowId = $workflow->id()->value();
        $this->workflowRepository->save($workflow);
        $this->handler = new ReorderStageHandler(
            $this->workflowRepository,
        );
    }

    public function testReorderStage(): void
    {
        $stages = $this->workflowRepository->findById(new WorkflowId($this->workflowId))->sortedStages();
        $stageId = $stages[0]->id();

        $this->handler->__invoke(new ReorderStageCommand(
            workflowId: $this->workflowId,
            stageId: $stageId,
            newPosition: 1,
        ));

        $updated = $this->workflowRepository->findById(new WorkflowId($this->workflowId))->sortedStages();
        $this->assertSame('Second', $updated[0]->name());
        $this->assertSame(0, $updated[0]->position());
        $this->assertSame('First', $updated[1]->name());
        $this->assertSame(1, $updated[1]->position());
    }

    public function testReorderStageWorkflowNotFoundThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->handler->__invoke(new ReorderStageCommand(
            workflowId: '00000000-0000-0000-0000-000000000000',
            stageId: 'any-stage',
            newPosition: 0,
        ));
    }
}

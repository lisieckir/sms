<?php

declare(strict_types=1);

namespace App\Tests\Unit\BoardManagement\Application\Command;

use App\BoardManagement\Application\Command\RemoveTransition\RemoveTransitionCommand;
use App\BoardManagement\Application\Command\RemoveTransition\RemoveTransitionHandler;
use App\BoardManagement\Domain\Model\Workflow;
use App\BoardManagement\Domain\Model\WorkflowId;
use App\Tests\InMemory\InMemoryWorkflowRepository;
use PHPUnit\Framework\TestCase;

class RemoveTransitionHandlerTest extends TestCase
{
    private InMemoryWorkflowRepository $workflowRepository;
    private RemoveTransitionHandler $handler;
    private string $workflowId;
    private string $fromStageId;
    private string $toStageId;

    protected function setUp(): void
    {
        $this->workflowRepository = new InMemoryWorkflowRepository();
        $workflow = Workflow::create(WorkflowId::generate(), 'Test', false);
        $workflow->addStage('To Do', 0);
        $workflow->addStage('Done', 1);
        $stages = $workflow->stages();
        $this->fromStageId = $stages[0]->id();
        $this->toStageId = $stages[1]->id();
        $workflow->addTransition($this->fromStageId, $this->toStageId);
        $this->workflowId = $workflow->id()->value();
        $this->workflowRepository->save($workflow);
        $this->handler = new RemoveTransitionHandler(
            $this->workflowRepository,
        );
    }

    public function testRemoveTransition(): void
    {
        $this->handler->__invoke(new RemoveTransitionCommand(
            workflowId: $this->workflowId,
            fromStageId: $this->fromStageId,
            toStageId: $this->toStageId,
        ));

        $workflow = $this->workflowRepository->findById(new WorkflowId($this->workflowId));
        $this->assertCount(0, $workflow->transitions());
        $this->assertFalse($workflow->canTransition($this->fromStageId, $this->toStageId));
    }

    public function testRemoveTransitionWorkflowNotFoundThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->handler->__invoke(new RemoveTransitionCommand(
            workflowId: '00000000-0000-0000-0000-000000000000',
            fromStageId: $this->fromStageId,
            toStageId: $this->toStageId,
        ));
    }
}

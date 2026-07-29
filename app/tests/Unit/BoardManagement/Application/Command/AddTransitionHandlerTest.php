<?php

declare(strict_types=1);

namespace App\Tests\Unit\BoardManagement\Application\Command;

use App\BoardManagement\Application\Command\AddTransition\AddTransitionCommand;
use App\BoardManagement\Application\Command\AddTransition\AddTransitionHandler;
use App\BoardManagement\Domain\Model\Workflow;
use App\BoardManagement\Domain\Model\WorkflowId;
use App\Tests\InMemory\InMemoryWorkflowRepository;
use PHPUnit\Framework\TestCase;

class AddTransitionHandlerTest extends TestCase
{
    private InMemoryWorkflowRepository $workflowRepository;
    private AddTransitionHandler $handler;
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
        $this->workflowId = $workflow->id()->value();
        $this->workflowRepository->save($workflow);
        $this->handler = new AddTransitionHandler(
            $this->workflowRepository,
        );
    }

    public function testAddTransition(): void
    {
        $this->handler->__invoke(new AddTransitionCommand(
            workflowId: $this->workflowId,
            fromStageId: $this->fromStageId,
            toStageId: $this->toStageId,
        ));

        $workflow = $this->workflowRepository->findById(new WorkflowId($this->workflowId));
        $this->assertCount(1, $workflow->transitions());
        $this->assertTrue($workflow->canTransition($this->fromStageId, $this->toStageId));
    }

    public function testAddTransitionWorkflowNotFoundThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->handler->__invoke(new AddTransitionCommand(
            workflowId: '00000000-0000-0000-0000-000000000000',
            fromStageId: $this->fromStageId,
            toStageId: $this->toStageId,
        ));
    }
}

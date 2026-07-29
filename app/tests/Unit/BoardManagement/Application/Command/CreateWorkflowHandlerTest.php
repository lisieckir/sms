<?php

declare(strict_types=1);

namespace App\Tests\Unit\BoardManagement\Application\Command;

use App\BoardManagement\Application\Command\CreateWorkflow\CreateWorkflowCommand;
use App\BoardManagement\Application\Command\CreateWorkflow\CreateWorkflowHandler;
use App\BoardManagement\Domain\Model\WorkflowId;
use App\Tests\InMemory\InMemoryWorkflowRepository;
use PHPUnit\Framework\TestCase;

class CreateWorkflowHandlerTest extends TestCase
{
    private InMemoryWorkflowRepository $workflowRepository;
    private CreateWorkflowHandler $handler;

    protected function setUp(): void
    {
        $this->workflowRepository = new InMemoryWorkflowRepository();
        $this->handler = new CreateWorkflowHandler(
            $this->workflowRepository,
        );
    }

    public function testCreateWorkflow(): void
    {
        $this->handler->__invoke(new CreateWorkflowCommand(
            name: 'Test Workflow',
        ));

        $workflows = $this->workflowRepository->findAll();
        $this->assertCount(1, $workflows);
        $this->assertSame('Test Workflow', $workflows[0]->name());
        $this->assertFalse($workflows[0]->isDefault());
    }

    public function testCreateDefaultWorkflow(): void
    {
        $this->handler->__invoke(new CreateWorkflowCommand(
            name: 'Default Workflow',
            isDefault: true,
        ));

        $workflow = $this->workflowRepository->findDefault();
        $this->assertNotNull($workflow);
        $this->assertSame('Default Workflow', $workflow->name());
        $this->assertTrue($workflow->isDefault());
    }
}

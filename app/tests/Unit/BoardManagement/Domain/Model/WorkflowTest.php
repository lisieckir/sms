<?php

declare(strict_types=1);

namespace App\Tests\Unit\BoardManagement\Domain\Model;

use App\BoardManagement\Domain\Model\Stage;
use App\BoardManagement\Domain\Model\Workflow;
use App\BoardManagement\Domain\Model\WorkflowId;
use App\BoardManagement\Domain\Event\WorkflowCreated;
use PHPUnit\Framework\TestCase;

class WorkflowTest extends TestCase
{
    public function testCreate(): void
    {
        $id = WorkflowId::generate();
        $workflow = Workflow::create($id, 'Simple Workflow');

        $this->assertTrue($id->equals($workflow->id()));
        $this->assertSame('Simple Workflow', $workflow->name());
        $this->assertEmpty($workflow->stages());
        $this->assertEmpty($workflow->transitions());
        $this->assertFalse($workflow->isDefault());
        $this->assertNull($workflow->getInitialStageId());

        $events = $workflow->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(WorkflowCreated::class, $events[0]);
    }

    public function testCreateDefault(): void
    {
        $id = WorkflowId::generate();
        $workflow = Workflow::createDefault($id);

        $this->assertTrue($workflow->isDefault());
        $this->assertCount(4, $workflow->stages());
        $this->assertCount(6, $workflow->transitions());

        $sorted = $workflow->sortedStages();
        $this->assertSame('To Do', $sorted[0]->name());
        $this->assertSame('In Progress', $sorted[1]->name());
        $this->assertSame('Review', $sorted[2]->name());
        $this->assertSame('Done', $sorted[3]->name());

        $this->assertSame($sorted[0]->id(), $workflow->getInitialStageId());
    }

    public function testAddStage(): void
    {
        $workflow = $this->createEmptyWorkflow();

        $workflow->addStage('Backlog', 0);

        $this->assertCount(1, $workflow->stages());
        $this->assertSame('Backlog', $workflow->stages()[0]->name());
        $this->assertSame(0, $workflow->stages()[0]->position());
    }

    public function testReorderStage(): void
    {
        $workflow = $this->createEmptyWorkflow();
        $workflow->addStage('A', 0);
        $workflow->addStage('B', 1);
        $stageA = $workflow->stages()[0];

        $workflow->reorderStage($stageA->id(), 5);

        $sorted = $workflow->sortedStages();
        $this->assertSame('B', $sorted[0]->name());
        $this->assertSame(0, $sorted[0]->position());
        $this->assertSame('A', $sorted[1]->name());
        $this->assertSame(5, $sorted[1]->position());
    }

    public function testReorderStageNotFoundThrowsException(): void
    {
        $workflow = $this->createEmptyWorkflow();

        $this->expectException(\InvalidArgumentException::class);
        $workflow->reorderStage('nonexistent-id', 1);
    }

    public function testRemoveStage(): void
    {
        $workflow = $this->createEmptyWorkflow();
        $workflow->addStage('A', 0);
        $workflow->addStage('B', 1);
        $stageA = $workflow->stages()[0];

        $workflow->removeStage($stageA->id());

        $this->assertCount(1, $workflow->stages());
        $this->assertSame('B', $workflow->stages()[0]->name());
    }

    public function testRemoveStageAlsoRemovesRelatedTransitions(): void
    {
        $workflow = $this->createEmptyWorkflow();
        $workflow->addStage('A', 0);
        $workflow->addStage('B', 1);
        $stages = $workflow->stages();
        $workflow->addTransition($stages[0]->id(), $stages[1]->id());

        $this->assertCount(1, $workflow->transitions());

        $workflow->removeStage($stages[0]->id());

        $this->assertCount(0, $workflow->transitions());
    }

    public function testRemoveStageNotFoundThrowsException(): void
    {
        $workflow = $this->createEmptyWorkflow();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Stage');
        $workflow->removeStage('nonexistent');
    }

    public function testAddTransition(): void
    {
        $workflow = $this->createEmptyWorkflow();
        $workflow->addStage('A', 0);
        $workflow->addStage('B', 1);
        $stages = $workflow->stages();

        $workflow->addTransition($stages[0]->id(), $stages[1]->id());

        $this->assertCount(1, $workflow->transitions());
    }

    public function testAddTransitionWithNonexistentFromStageThrowsException(): void
    {
        $workflow = $this->createEmptyWorkflow();
        $workflow->addStage('B', 1);
        $stageB = $workflow->stages()[0];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source stage');
        $workflow->addTransition('bad', $stageB->id());
    }

    public function testAddTransitionDuplicateIsNoOp(): void
    {
        $workflow = $this->createEmptyWorkflow();
        $workflow->addStage('A', 0);
        $workflow->addStage('B', 1);
        $stages = $workflow->stages();

        $workflow->addTransition($stages[0]->id(), $stages[1]->id());
        $workflow->addTransition($stages[0]->id(), $stages[1]->id());

        $this->assertCount(1, $workflow->transitions());
    }

    public function testRemoveTransition(): void
    {
        $workflow = $this->createEmptyWorkflow();
        $workflow->addStage('A', 0);
        $workflow->addStage('B', 1);
        $stages = $workflow->stages();
        $workflow->addTransition($stages[0]->id(), $stages[1]->id());

        $workflow->removeTransition($stages[0]->id(), $stages[1]->id());

        $this->assertCount(0, $workflow->transitions());
    }

    public function testRemoveTransitionNotFoundThrowsException(): void
    {
        $workflow = $this->createEmptyWorkflow();

        $this->expectException(\InvalidArgumentException::class);
        $workflow->removeTransition('a', 'b');
    }

    public function testCanTransition(): void
    {
        $workflow = $this->createEmptyWorkflow();
        $workflow->addStage('A', 0);
        $workflow->addStage('B', 1);
        $stages = $workflow->stages();
        $workflow->addTransition($stages[0]->id(), $stages[1]->id());

        $this->assertTrue($workflow->canTransition($stages[0]->id(), $stages[1]->id()));
        $this->assertFalse($workflow->canTransition($stages[1]->id(), $stages[0]->id()));
        $this->assertFalse($workflow->canTransition('unknown', $stages[0]->id()));
    }

    public function testSortedStages(): void
    {
        $workflow = $this->createEmptyWorkflow();
        $workflow->addStage('B', 5);
        $workflow->addStage('A', 1);
        $workflow->addStage('C', 3);

        $sorted = $workflow->sortedStages();
        $this->assertCount(3, $sorted);
        $this->assertSame('A', $sorted[0]->name());
        $this->assertSame('C', $sorted[1]->name());
        $this->assertSame('B', $sorted[2]->name());
    }

    private function createEmptyWorkflow(): Workflow
    {
        $workflow = Workflow::create(WorkflowId::generate(), 'Test');
        $workflow->releaseEvents();
        return $workflow;
    }
}

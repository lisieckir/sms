<?php

declare(strict_types=1);

namespace App\BoardManagement\Domain\Model;

use App\BoardManagement\Domain\Event\WorkflowCreated;
use App\BoardManagement\Domain\Event\Trait\EventRecordingCapabilities;

class Workflow
{
    use EventRecordingCapabilities;

    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    private function __construct(
        private WorkflowId $id,
        private string $name,
        private array $stages,
        private array $transitions,
        private bool $isDefault,
    ) {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public static function create(WorkflowId $id, string $name, bool $isDefault = false): self
    {
        $workflow = new self($id, $name, [], [], $isDefault);
        $workflow->recordEvent(new WorkflowCreated($id, $name, new \DateTimeImmutable()));
        return $workflow;
    }

    public static function createDefault(WorkflowId $id): self
    {
        $workflow = new self($id, 'Default Workflow', [], [], true);

        $todo = Stage::create('To Do', 0);
        $inProgress = Stage::create('In Progress', 1);
        $review = Stage::create('Review', 2);
        $done = Stage::create('Done', 3);

        $workflow->stages = [$todo, $inProgress, $review, $done];
        $workflow->transitions = [
            Transition::create($todo->id(), $inProgress->id()),
            Transition::create($inProgress->id(), $review->id()),
            Transition::create($review->id(), $done->id()),
            Transition::create($inProgress->id(), $todo->id()),
            Transition::create($review->id(), $inProgress->id()),
            Transition::create($done->id(), $review->id()),
        ];

        $workflow->recordEvent(new WorkflowCreated($id, 'Default Workflow', new \DateTimeImmutable()));
        return $workflow;
    }

    public function addStage(string $name, int $position): void
    {
        $stage = Stage::create($name, $position);
        $this->stages[] = $stage;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function reorderStage(string $stageId, int $newPosition): void
    {
        foreach ($this->stages as $i => $stage) {
            if ($stage->id() === $stageId) {
                $this->stages[$i] = $stage->move($newPosition);
                $this->updatedAt = new \DateTimeImmutable();
                return;
            }
        }

        throw new \InvalidArgumentException("Stage $stageId not found");
    }

    public function removeStage(string $stageId): void
    {
        foreach ($this->stages as $i => $stage) {
            if ($stage->id() === $stageId) {
                unset($this->stages[$i]);
                $this->stages = array_values($this->stages);
                $this->transitions = array_values(
                    array_filter($this->transitions, fn(Transition $t) =>
                        $t->fromStageId() !== $stageId && $t->toStageId() !== $stageId
                    )
                );
                $this->updatedAt = new \DateTimeImmutable();
                return;
            }
        }

        throw new \InvalidArgumentException("Stage $stageId not found");
    }

    public function addTransition(string $fromStageId, string $toStageId): void
    {
        if (!$this->hasStage($fromStageId)) {
            throw new \InvalidArgumentException("Source stage $fromStageId not found");
        }
        if (!$this->hasStage($toStageId)) {
            throw new \InvalidArgumentException("Target stage $toStageId not found");
        }

        foreach ($this->transitions as $t) {
            if ($t->matches($fromStageId, $toStageId)) {
                return;
            }
        }

        $this->transitions[] = Transition::create($fromStageId, $toStageId);
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function removeTransition(string $fromStageId, string $toStageId): void
    {
        foreach ($this->transitions as $i => $t) {
            if ($t->matches($fromStageId, $toStageId)) {
                unset($this->transitions[$i]);
                $this->transitions = array_values($this->transitions);
                $this->updatedAt = new \DateTimeImmutable();
                return;
            }
        }

        throw new \InvalidArgumentException("Transition $fromStageId -> $toStageId not found");
    }

    public function canTransition(string $fromStageId, string $toStageId): bool
    {
        foreach ($this->transitions as $t) {
            if ($t->matches($fromStageId, $toStageId)) {
                return true;
            }
        }
        return false;
    }

    public function getInitialStageId(): ?string
    {
        $stages = $this->sortedStages();
        return !empty($stages) ? $stages[0]->id() : null;
    }

    public function sortedStages(): array
    {
        $stages = $this->stages;
        usort($stages, fn(Stage $a, Stage $b) => $a->position() <=> $b->position());
        return $stages;
    }

    private function hasStage(string $stageId): bool
    {
        foreach ($this->stages as $stage) {
            if ($stage->id() === $stageId) {
                return true;
            }
        }
        return false;
    }

    public function id(): WorkflowId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function stages(): array
    {
        return $this->stages;
    }

    public function transitions(): array
    {
        return $this->transitions;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}

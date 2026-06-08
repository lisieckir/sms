<?php

declare(strict_types=1);

namespace App\Tests\InMemory;

use App\BoardManagement\Domain\Model\Workflow;
use App\BoardManagement\Domain\Model\WorkflowId;
use App\BoardManagement\Domain\Model\WorkflowRepositoryInterface;

class InMemoryWorkflowRepository implements WorkflowRepositoryInterface
{
    /** @var array<string, Workflow> */
    private array $workflows = [];

    public function save(Workflow $workflow): void
    {
        $this->workflows[$workflow->id()->value()] = $workflow;
    }

    public function findById(WorkflowId $id): ?Workflow
    {
        return $this->workflows[$id->value()] ?? null;
    }

    public function findDefault(): ?Workflow
    {
        foreach ($this->workflows as $workflow) {
            if ($workflow->isDefault()) {
                return $workflow;
            }
        }
        return null;
    }

    public function findAll(): array
    {
        return array_values($this->workflows);
    }

    public function nextIdentity(): WorkflowId
    {
        return WorkflowId::generate();
    }
}

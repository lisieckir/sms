<?php

declare(strict_types=1);

namespace App\BoardManagement\Domain\Model;

interface WorkflowRepositoryInterface
{
    public function save(Workflow $workflow): void;
    public function findById(WorkflowId $id): ?Workflow;
    public function findDefault(): ?Workflow;
    public function findAll(): array;
    public function nextIdentity(): WorkflowId;
}

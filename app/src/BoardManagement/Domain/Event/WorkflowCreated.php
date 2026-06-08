<?php

declare(strict_types=1);

namespace App\BoardManagement\Domain\Event;

use App\BoardManagement\Domain\Model\WorkflowId;

final class WorkflowCreated implements DomainEvent
{
    public function __construct(
        private WorkflowId $workflowId,
        private string $name,
        private \DateTimeImmutable $occurredAt,
    ) {}

    public function workflowId(): WorkflowId
    {
        return $this->workflowId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}

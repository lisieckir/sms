<?php

declare(strict_types=1);

namespace App\TaskManagement\Domain\Event;

interface DomainEvent
{
    public function occurredAt(): \DateTimeImmutable;
}

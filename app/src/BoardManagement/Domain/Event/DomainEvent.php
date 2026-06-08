<?php

declare(strict_types=1);

namespace App\BoardManagement\Domain\Event;

interface DomainEvent
{
    public function occurredAt(): \DateTimeImmutable;
}

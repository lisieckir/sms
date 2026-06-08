<?php

declare(strict_types=1);

namespace App\ClientManagement\Domain\Event;

interface DomainEvent
{
    public function occurredAt(): \DateTimeImmutable;
}

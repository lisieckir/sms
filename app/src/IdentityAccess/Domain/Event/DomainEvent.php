<?php

declare(strict_types=1);

namespace App\IdentityAccess\Domain\Event;

interface DomainEvent
{
    public function occurredAt(): \DateTimeImmutable;
}

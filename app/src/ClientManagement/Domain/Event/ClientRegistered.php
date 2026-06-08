<?php

declare(strict_types=1);

namespace App\ClientManagement\Domain\Event;

use App\ClientManagement\Domain\Model\ClientId;

final class ClientRegistered implements DomainEvent
{
    public function __construct(
        private ClientId $clientId,
        private string $name,
        private \DateTimeImmutable $occurredAt,
    ) {}

    public function clientId(): ClientId { return $this->clientId; }
    public function name(): string { return $this->name; }
    public function occurredAt(): \DateTimeImmutable { return $this->occurredAt; }
}

<?php

declare(strict_types=1);

namespace App\ClientManagement\Domain\Event\Trait;

use App\ClientManagement\Domain\Event\DomainEvent;

trait EventRecordingCapabilities
{
    private array $recordedEvents = [];

    public function recordEvent(DomainEvent $event): void
    {
        $this->recordedEvents[] = $event;
    }

    public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];
        return $events;
    }
}

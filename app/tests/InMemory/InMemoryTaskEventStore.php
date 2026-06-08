<?php

declare(strict_types=1);

namespace App\Tests\InMemory;

class InMemoryTaskEventStore
{
    public array $events = [];

    public function append(
        string $eventId,
        string $taskId,
        ?string $userId,
        string $type,
        array $data,
        \DateTimeImmutable $occurredAt,
    ): void {
        $this->events[] = compact('eventId', 'taskId', 'userId', 'type', 'data', 'occurredAt');
    }

    public function findByTask(string $taskId): array
    {
        return array_values(array_filter($this->events, fn(array $e) => $e['taskId'] === $taskId));
    }
}

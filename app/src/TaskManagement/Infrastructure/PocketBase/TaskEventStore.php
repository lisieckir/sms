<?php

declare(strict_types=1);

namespace App\TaskManagement\Infrastructure\PocketBase;

use App\Core\Infrastructure\PocketBase\PocketBaseClient;

final readonly class TaskEventStore
{
    private const COLLECTION = 'task_events';

    public function __construct(
        private PocketBaseClient $pb,
    ) {}

    public function append(
        string $eventId,
        string $taskId,
        ?string $userId,
        string $type,
        array $data,
        \DateTimeImmutable $occurredAt,
    ): void {
        $this->pb->create(self::COLLECTION, [
            'eventId' => $eventId,
            'taskId' => $taskId,
            'userId' => $userId,
            'type' => $type,
            'data' => $data,
            'occurredAt' => $occurredAt->format('c'),
        ]);
    }

    public function findByTask(string $taskId): array
    {
        $result = $this->pb->list(self::COLLECTION, [
            'filter' => sprintf('taskId="%s"', $taskId),
            'sort' => '-occurredAt',
        ]);
        return $result['items'];
    }
}

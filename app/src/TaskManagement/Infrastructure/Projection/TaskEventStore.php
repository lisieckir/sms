<?php

declare(strict_types=1);

namespace App\TaskManagement\Infrastructure\Projection;

use Doctrine\ODM\MongoDB\DocumentManager;
use App\TaskManagement\Infrastructure\Doctrine\TaskEventDocument;

class TaskEventStore
{
    public function __construct(
        private DocumentManager $dm,
    ) {}

    public function append(
        string $eventId,
        string $taskId,
        ?string $userId,
        string $type,
        array $data,
        \DateTimeImmutable $occurredAt,
    ): void {
        $doc = new TaskEventDocument();
        $doc->eventId = $eventId;
        $doc->taskId = $taskId;
        $doc->userId = $userId;
        $doc->type = $type;
        $doc->data = $data;
        $doc->occurredAt = \DateTime::createFromImmutable($occurredAt);

        $this->dm->persist($doc);
        $this->dm->flush();
    }

    public function findByTask(string $taskId): array
    {
        $repo = $this->dm->getRepository(TaskEventDocument::class);
        return $repo->findBy(['taskId' => $taskId], ['occurredAt' => 'desc']);
    }
}

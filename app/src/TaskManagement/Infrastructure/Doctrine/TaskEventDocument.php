<?php

declare(strict_types=1);

namespace App\TaskManagement\Infrastructure\Doctrine;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document(collection: 'task_events')]
class TaskEventDocument
{
    #[MongoDB\Id]
    public ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    public string $eventId;

    #[MongoDB\Field(type: 'string')]
    public string $taskId;

    #[MongoDB\Field(type: 'string', nullable: true)]
    public ?string $userId = null;

    #[MongoDB\Field(type: 'string')]
    public string $type;

    #[MongoDB\Field(type: 'hash')]
    public array $data = [];

    #[MongoDB\Field(type: 'date')]
    public \DateTime $occurredAt;
}

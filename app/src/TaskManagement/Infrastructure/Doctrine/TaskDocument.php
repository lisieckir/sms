<?php

declare(strict_types=1);

namespace App\TaskManagement\Infrastructure\Doctrine;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document(collection: 'tasks')]
class TaskDocument
{
    #[MongoDB\Id]
    public ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    public string $taskId;

    #[MongoDB\Field(type: 'string')]
    public string $title;

    #[MongoDB\Field(type: 'string')]
    public string $description;

    #[MongoDB\Field(type: 'string')]
    public string $creatorId;

    #[MongoDB\Field(type: 'string', nullable: true)]
    public ?string $assigneeId = null;

    #[MongoDB\Field(type: 'string', nullable: true)]
    public ?string $clientId = null;

    #[MongoDB\Field(type: 'string')]
    public string $stageId;

    #[MongoDB\Field(type: 'int')]
    public int $position = 0;

    #[MongoDB\Field(type: 'string', nullable: true)]
    public ?string $parentTaskId = null;

    #[MongoDB\Field(type: 'string')]
    public string $status = 'active';

    #[MongoDB\Field(type: 'collection')]
    public array $comments = [];

    #[MongoDB\Field(type: 'collection')]
    public array $worklogs = [];

    #[MongoDB\Field(type: 'int')]
    public int $totalTimeSpent = 0;

    #[MongoDB\Field(type: 'date')]
    public \DateTime $createdAt;

    #[MongoDB\Field(type: 'date')]
    public \DateTime $updatedAt;
}

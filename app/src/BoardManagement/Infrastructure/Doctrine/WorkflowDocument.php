<?php

declare(strict_types=1);

namespace App\BoardManagement\Infrastructure\Doctrine;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document(collection: 'workflows')]
class WorkflowDocument
{
    #[MongoDB\Id]
    public ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    public string $workflowId;

    #[MongoDB\Field(type: 'string')]
    public string $name;

    #[MongoDB\Field(type: 'collection')]
    public array $stages = [];

    #[MongoDB\Field(type: 'collection')]
    public array $transitions = [];

    #[MongoDB\Field(type: 'bool')]
    public bool $isDefault = false;

    #[MongoDB\Field(type: 'date')]
    public \DateTime $createdAt;

    #[MongoDB\Field(type: 'date')]
    public \DateTime $updatedAt;
}

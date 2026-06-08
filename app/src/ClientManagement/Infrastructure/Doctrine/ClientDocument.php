<?php

declare(strict_types=1);

namespace App\ClientManagement\Infrastructure\Doctrine;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document(collection: 'clients')]
class ClientDocument
{
    #[MongoDB\Id]
    public ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    public string $clientId;

    #[MongoDB\Field(type: 'string')]
    public string $nip;

    #[MongoDB\Field(type: 'string')]
    public string $name;

    #[MongoDB\Field(type: 'string')]
    public string $address;

    #[MongoDB\Field(type: 'string')]
    public string $country;

    #[MongoDB\Field(type: 'string')]
    public string $email;

    #[MongoDB\Field(type: 'string')]
    public string $description = '';

    #[MongoDB\Field(type: 'collection')]
    public array $contacts = [];

    #[MongoDB\Field(type: 'string')]
    public string $status = 'active';

    #[MongoDB\Field(type: 'date')]
    public \DateTime $createdAt;

    #[MongoDB\Field(type: 'date')]
    public \DateTime $updatedAt;
}

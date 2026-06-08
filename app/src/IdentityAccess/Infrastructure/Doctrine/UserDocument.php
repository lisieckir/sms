<?php

declare(strict_types=1);

namespace App\IdentityAccess\Infrastructure\Doctrine;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document(collection: 'users')]
class UserDocument
{
    #[MongoDB\Id]
    public ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    public string $userId;

    #[MongoDB\Field(type: 'string')]
    public string $firstName;

    #[MongoDB\Field(type: 'string')]
    public string $lastName;

    #[MongoDB\Field(type: 'string')]
    public string $email;

    #[MongoDB\Field(type: 'string')]
    public string $username;

    #[MongoDB\Field(type: 'string')]
    public string $password;

    #[MongoDB\Field(type: 'collection')]
    public array $roles = [];

    #[MongoDB\Field(type: 'string')]
    public string $status = 'active';

    #[MongoDB\Field(type: 'date')]
    public \DateTime $createdAt;

    #[MongoDB\Field(type: 'date')]
    public \DateTime $updatedAt;
}

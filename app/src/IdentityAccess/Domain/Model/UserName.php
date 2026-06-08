<?php

declare(strict_types=1);

namespace App\IdentityAccess\Domain\Model;

final readonly class UserName
{
    public function __construct(
        private string $firstName,
        private string $lastName,
    ) {
        if (empty(trim($firstName)) || empty(trim($lastName))) {
            throw new \InvalidArgumentException('First name and last name cannot be empty');
        }
    }

    public function firstName(): string
    {
        return $this->firstName;
    }

    public function lastName(): string
    {
        return $this->lastName;
    }

    public function fullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }
}

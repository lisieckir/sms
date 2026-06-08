<?php

declare(strict_types=1);

namespace App\IdentityAccess\Application\Command\RegisterUser;

final readonly class RegisterUserCommand
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $username,
        public string $plainPassword,
        public bool $isAdmin = false,
    ) {
    }
}

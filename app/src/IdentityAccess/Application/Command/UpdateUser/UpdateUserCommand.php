<?php

declare(strict_types=1);

namespace App\IdentityAccess\Application\Command\UpdateUser;

final readonly class UpdateUserCommand
{
    public function __construct(
        public string $userId,
        public string $email,
        public bool $isAdmin,
    ) {}
}

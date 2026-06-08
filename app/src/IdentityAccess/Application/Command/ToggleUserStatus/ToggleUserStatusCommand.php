<?php

declare(strict_types=1);

namespace App\IdentityAccess\Application\Command\ToggleUserStatus;

final readonly class ToggleUserStatusCommand
{
    public function __construct(
        public string $userId,
    ) {}
}

<?php

declare(strict_types=1);

namespace App\IdentityAccess\Domain\Event;

use App\IdentityAccess\Domain\Model\UserId;

final readonly class UserPasswordChanged implements DomainEvent
{
    public function __construct(
        public UserId $userId,
        public \DateTimeImmutable $occurredAt,
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}

<?php

declare(strict_types=1);

namespace App\IdentityAccess\Infrastructure\Security;

use App\IdentityAccess\Domain\Model\User;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class UserIdentity implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private User $user,
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getRoles(): array
    {
        return $this->user->roles();
    }

    public function getPassword(): ?string
    {
        return $this->user->password()->hashedValue();
    }

    public function getUserIdentifier(): string
    {
        return $this->user->email()->value();
    }

    public function getUserId(): string
    {
        return $this->user->id()->value();
    }

    public function eraseCredentials(): void
    {
    }
}

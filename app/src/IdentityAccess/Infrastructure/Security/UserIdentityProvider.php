<?php

declare(strict_types=1);

namespace App\IdentityAccess\Infrastructure\Security;

use App\IdentityAccess\Domain\Model\UserEmail;
use App\IdentityAccess\Domain\Model\UserRepositoryInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final readonly class UserIdentityProvider implements UserProviderInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userRepository->findByEmail(new UserEmail($identifier));
        if ($user === null) {
            $user = $this->userRepository->findByUsername($identifier);
        }
        if ($user === null) {
            throw new UserNotFoundException(sprintf('User "%s" not found', $identifier));
        }
        return new UserIdentity($user);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return $class === UserIdentity::class;
    }
}

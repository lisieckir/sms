<?php

declare(strict_types=1);

namespace App\IdentityAccess\Application\Command\UpdateUser;

use App\IdentityAccess\Domain\Model\UserEmail;
use App\IdentityAccess\Domain\Model\UserId;
use App\IdentityAccess\Domain\Model\UserRepositoryInterface;

final readonly class UpdateUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(UpdateUserCommand $command): void
    {
        $user = $this->userRepository->findById(new UserId($command->userId));
        if ($user === null) {
            throw new \RuntimeException('User not found: ' . $command->userId);
        }

        $newEmail = new UserEmail($command->email);

        if ($newEmail->value() !== $user->email()->value()) {
            $existing = $this->userRepository->findByEmail($newEmail);
            if ($existing !== null && $existing->id()->value() !== $user->id()->value()) {
                throw new \RuntimeException('Email already in use: ' . $command->email);
            }
        }

        $user->changeEmail($newEmail);

        $roles = $command->isAdmin ? ['ROLE_ADMIN', 'ROLE_USER'] : ['ROLE_USER'];
        $user->setRoles($roles);

        $this->userRepository->save($user);
    }
}

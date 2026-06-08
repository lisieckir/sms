<?php

declare(strict_types=1);

namespace App\IdentityAccess\Application\Command\ToggleUserStatus;

use App\IdentityAccess\Domain\Model\UserId;
use App\IdentityAccess\Domain\Model\UserRepositoryInterface;

final readonly class ToggleUserStatusHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(ToggleUserStatusCommand $command): void
    {
        $user = $this->userRepository->findById(new UserId($command->userId));
        if ($user === null) {
            throw new \RuntimeException('User not found: ' . $command->userId);
        }

        if ($user->isActive()) {
            $user->deactivate();
        } else {
            $user->activate();
        }

        $this->userRepository->save($user);
    }
}

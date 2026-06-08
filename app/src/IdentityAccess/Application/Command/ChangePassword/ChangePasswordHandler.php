<?php

declare(strict_types=1);

namespace App\IdentityAccess\Application\Command\ChangePassword;

use App\IdentityAccess\Domain\Exception\UserNotFound;
use App\IdentityAccess\Domain\Model\UserPassword;
use App\IdentityAccess\Domain\Model\UserRepositoryInterface;
use App\IdentityAccess\Domain\Model\UserId;
use App\IdentityAccess\Domain\Service\PasswordHasherInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class ChangePasswordHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
        private MessageBusInterface $eventBus,
    ) {
    }

    public function __invoke(ChangePasswordCommand $command): void
    {
        $user = $this->userRepository->findById(new UserId($command->userId));
        if ($user === null) {
            throw UserNotFound::withId($command->userId);
        }

        $oldPassword = new UserPassword($command->oldPassword);
        $newPassword = new UserPassword($command->newPassword);
        $user->changePassword($oldPassword, $newPassword, $this->passwordHasher);

        $this->userRepository->save($user);

        foreach ($user->releaseEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}

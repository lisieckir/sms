<?php

declare(strict_types=1);

namespace App\IdentityAccess\Application\Command\RegisterUser;

use App\IdentityAccess\Domain\Model\User;
use App\IdentityAccess\Domain\Model\UserEmail;
use App\IdentityAccess\Domain\Model\UserId;
use App\IdentityAccess\Domain\Model\UserName;
use App\IdentityAccess\Domain\Model\UserPassword;
use App\IdentityAccess\Domain\Model\UserRepositoryInterface;
use App\IdentityAccess\Domain\Service\PasswordHasherInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class RegisterUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
        private MessageBusInterface $eventBus,
    ) {
    }

    public function __invoke(RegisterUserCommand $command): void
    {
        $email = new UserEmail($command->email);
        if ($this->userRepository->findByEmail($email) !== null) {
            throw new \RuntimeException('Email already in use: ' . $command->email);
        }

        $id = $this->userRepository->nextIdentity();
        $name = new UserName($command->firstName, $command->lastName);
        $password = new UserPassword($command->plainPassword);

        if ($command->isAdmin) {
            $user = User::registerAdmin($id, $name, $email, $command->username, $password, $this->passwordHasher);
        } else {
            $user = User::register($id, $name, $email, $command->username, $password, $this->passwordHasher);
        }

        $this->userRepository->save($user);

        foreach ($user->releaseEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}

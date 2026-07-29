<?php

declare(strict_types=1);

namespace App\Tests\Unit\IdentityAccess\Application\Command;

use App\IdentityAccess\Application\Command\UpdateUser\UpdateUserCommand;
use App\IdentityAccess\Application\Command\UpdateUser\UpdateUserHandler;
use App\IdentityAccess\Domain\Model\User;
use App\IdentityAccess\Domain\Model\UserEmail;
use App\IdentityAccess\Domain\Model\UserId;
use App\IdentityAccess\Domain\Model\UserName;
use App\IdentityAccess\Domain\Model\UserPassword;
use App\Tests\InMemory\InMemoryPasswordHasher;
use App\Tests\InMemory\InMemoryUserRepository;
use PHPUnit\Framework\TestCase;

class UpdateUserHandlerTest extends TestCase
{
    private InMemoryUserRepository $userRepository;
    private InMemoryPasswordHasher $passwordHasher;
    private UpdateUserHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = new InMemoryUserRepository();
        $this->passwordHasher = new InMemoryPasswordHasher();
        $this->handler = new UpdateUserHandler(
            $this->userRepository,
        );
    }

    public function testUpdateEmail(): void
    {
        $user = $this->createUser();
        $userId = $user->id()->value();

        $this->handler->__invoke(new UpdateUserCommand(
            userId: $userId,
            email: 'updated@example.com',
            isAdmin: false,
        ));

        $updated = $this->userRepository->findById(new UserId($userId));
        $this->assertSame('updated@example.com', $updated->email()->value());
    }

    public function testUpdateRoleToAdmin(): void
    {
        $user = $this->createUser();
        $userId = $user->id()->value();

        $this->handler->__invoke(new UpdateUserCommand(
            userId: $userId,
            email: $user->email()->value(),
            isAdmin: true,
        ));

        $updated = $this->userRepository->findById(new UserId($userId));
        $this->assertContains('ROLE_ADMIN', $updated->roles());
    }

    public function testUpdateEmailAlreadyInUseThrowsException(): void
    {
        $user1 = $this->createUser('user1@example.com', 'user1');
        $user2 = $this->createUser('user2@example.com', 'user2');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Email already in use: user1@example.com');

        $this->handler->__invoke(new UpdateUserCommand(
            userId: $user2->id()->value(),
            email: 'user1@example.com',
            isAdmin: false,
        ));
    }

    public function testUpdateUserNotFoundThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->handler->__invoke(new UpdateUserCommand(
            userId: '00000000-0000-0000-0000-000000000000',
            email: 'any@example.com',
            isAdmin: false,
        ));
    }

    private function createUser(string $email = 'john@example.com', string $username = 'johndoe'): User
    {
        $id = $this->userRepository->nextIdentity();
        $user = User::register(
            $id,
            new UserName('John', 'Doe'),
            new UserEmail($email),
            $username,
            new UserPassword('pass'),
            $this->passwordHasher,
        );
        $user->releaseEvents();
        $this->userRepository->save($user);
        return $user;
    }
}

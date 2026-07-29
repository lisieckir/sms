<?php

declare(strict_types=1);

namespace App\Tests\Unit\IdentityAccess\Application\Command;

use App\IdentityAccess\Application\Command\ToggleUserStatus\ToggleUserStatusCommand;
use App\IdentityAccess\Application\Command\ToggleUserStatus\ToggleUserStatusHandler;
use App\IdentityAccess\Domain\Model\User;
use App\IdentityAccess\Domain\Model\UserEmail;
use App\IdentityAccess\Domain\Model\UserId;
use App\IdentityAccess\Domain\Model\UserName;
use App\IdentityAccess\Domain\Model\UserPassword;
use App\Tests\InMemory\InMemoryPasswordHasher;
use App\Tests\InMemory\InMemoryUserRepository;
use PHPUnit\Framework\TestCase;

class ToggleUserStatusHandlerTest extends TestCase
{
    private InMemoryUserRepository $userRepository;
    private InMemoryPasswordHasher $passwordHasher;
    private ToggleUserStatusHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = new InMemoryUserRepository();
        $this->passwordHasher = new InMemoryPasswordHasher();
        $this->handler = new ToggleUserStatusHandler(
            $this->userRepository,
        );
    }

    public function testDeactivateUser(): void
    {
        $user = $this->createUser();
        $userId = $user->id()->value();

        $this->handler->__invoke(new ToggleUserStatusCommand(userId: $userId));

        $updated = $this->userRepository->findById(new UserId($userId));
        $this->assertFalse($updated->isActive());
    }

    public function testActivateUser(): void
    {
        $user = $this->createUser();
        $user->deactivate();
        $this->userRepository->save($user);
        $userId = $user->id()->value();

        $this->handler->__invoke(new ToggleUserStatusCommand(userId: $userId));

        $updated = $this->userRepository->findById(new UserId($userId));
        $this->assertTrue($updated->isActive());
    }

    public function testToggleUserNotFoundThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->handler->__invoke(new ToggleUserStatusCommand(
            userId: '00000000-0000-0000-0000-000000000000',
        ));
    }

    private function createUser(): User
    {
        $id = $this->userRepository->nextIdentity();
        $user = User::register(
            $id,
            new UserName('John', 'Doe'),
            new UserEmail('john@example.com'),
            'johndoe',
            new UserPassword('pass'),
            $this->passwordHasher,
        );
        $user->releaseEvents();
        $this->userRepository->save($user);
        return $user;
    }
}

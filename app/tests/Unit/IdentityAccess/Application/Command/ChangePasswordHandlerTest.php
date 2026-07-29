<?php

declare(strict_types=1);

namespace App\Tests\Unit\IdentityAccess\Application\Command;

use App\IdentityAccess\Application\Command\ChangePassword\ChangePasswordCommand;
use App\IdentityAccess\Application\Command\ChangePassword\ChangePasswordHandler;
use App\IdentityAccess\Domain\Model\User;
use App\IdentityAccess\Domain\Model\UserEmail;
use App\IdentityAccess\Domain\Model\UserId;
use App\IdentityAccess\Domain\Model\UserName;
use App\IdentityAccess\Domain\Model\UserPassword;
use App\IdentityAccess\Domain\Event\UserPasswordChanged;
use App\Tests\InMemory\InMemoryPasswordHasher;
use App\Tests\InMemory\InMemoryUserRepository;
use App\Tests\InMemory\SpyEventBus;
use PHPUnit\Framework\TestCase;

class ChangePasswordHandlerTest extends TestCase
{
    private InMemoryUserRepository $userRepository;
    private InMemoryPasswordHasher $passwordHasher;
    private SpyEventBus $eventBus;
    private ChangePasswordHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = new InMemoryUserRepository();
        $this->passwordHasher = new InMemoryPasswordHasher();
        $this->eventBus = new SpyEventBus();
        $this->handler = new ChangePasswordHandler(
            $this->userRepository,
            $this->passwordHasher,
            $this->eventBus,
        );
    }

    public function testChangePassword(): void
    {
        $user = $this->createUser();
        $userId = $user->id()->value();

        $this->handler->__invoke(new ChangePasswordCommand(
            userId: $userId,
            oldPassword: 'oldpass',
            newPassword: 'newpass',
        ));

        $updated = $this->userRepository->findById(new UserId($userId));
        $this->assertNotNull($updated);
        $this->assertTrue(
            $this->passwordHasher->verify($updated->password(), 'newpass')
        );
    }

    public function testChangePasswordWithWrongOldPasswordThrowsException(): void
    {
        $user = $this->createUser();
        $userId = $user->id()->value();

        $this->expectException(\InvalidArgumentException::class);
        $this->handler->__invoke(new ChangePasswordCommand(
            userId: $userId,
            oldPassword: 'wrongpass',
            newPassword: 'newpass',
        ));
    }

    public function testChangePasswordUserNotFoundThrowsException(): void
    {
        $this->expectException(\App\IdentityAccess\Domain\Exception\UserNotFound::class);

        $this->handler->__invoke(new ChangePasswordCommand(
            userId: '00000000-0000-0000-0000-000000000000',
            oldPassword: 'oldpass',
            newPassword: 'newpass',
        ));
    }

    public function testChangePasswordDispatchesEvent(): void
    {
        $user = $this->createUser();

        $this->handler->__invoke(new ChangePasswordCommand(
            userId: $user->id()->value(),
            oldPassword: 'oldpass',
            newPassword: 'newpass',
        ));

        $this->assertCount(1, $this->eventBus->messages);
        $this->assertInstanceOf(UserPasswordChanged::class, $this->eventBus->messages[0]);
    }

    private function createUser(): User
    {
        $id = $this->userRepository->nextIdentity();
        $user = User::register(
            $id,
            new UserName('John', 'Doe'),
            new UserEmail('john@example.com'),
            'johndoe',
            new UserPassword('oldpass'),
            $this->passwordHasher,
        );
        $user->releaseEvents();
        $this->userRepository->save($user);
        return $user;
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\IdentityAccess\Application\Command;

use App\IdentityAccess\Application\Command\RegisterUser\RegisterUserCommand;
use App\IdentityAccess\Application\Command\RegisterUser\RegisterUserHandler;
use App\IdentityAccess\Domain\Model\UserEmail;
use App\IdentityAccess\Domain\Event\UserRegistered;
use App\Tests\InMemory\InMemoryPasswordHasher;
use App\Tests\InMemory\InMemoryUserRepository;
use App\Tests\InMemory\SpyEventBus;
use PHPUnit\Framework\TestCase;

class RegisterUserHandlerTest extends TestCase
{
    private InMemoryUserRepository $userRepository;
    private InMemoryPasswordHasher $passwordHasher;
    private SpyEventBus $eventBus;
    private RegisterUserHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = new InMemoryUserRepository();
        $this->passwordHasher = new InMemoryPasswordHasher();
        $this->eventBus = new SpyEventBus();
        $this->handler = new RegisterUserHandler(
            $this->userRepository,
            $this->passwordHasher,
            $this->eventBus,
        );
    }

    public function testRegisterUser(): void
    {
        $this->handler->__invoke(new RegisterUserCommand(
            firstName: 'John',
            lastName: 'Doe',
            email: 'john@example.com',
            username: 'johndoe',
            plainPassword: 'secret123',
        ));

        $user = $this->userRepository->findByEmail(new UserEmail('john@example.com'));
        $this->assertNotNull($user);
        $this->assertSame('John', $user->name()->firstName());
        $this->assertSame('Doe', $user->name()->lastName());
        $this->assertSame('johndoe', $user->username());
        $this->assertTrue($user->isActive());
        $this->assertContains('ROLE_USER', $user->roles());
        $this->assertNotContains('ROLE_ADMIN', $user->roles());
    }

    public function testRegisterUserAsAdmin(): void
    {
        $this->handler->__invoke(new RegisterUserCommand(
            firstName: 'Admin',
            lastName: 'User',
            email: 'admin@example.com',
            username: 'adminuser',
            plainPassword: 'admin123',
            isAdmin: true,
        ));

        $user = $this->userRepository->findByEmail(new UserEmail('admin@example.com'));
        $this->assertNotNull($user);
        $this->assertContains('ROLE_ADMIN', $user->roles());
        $this->assertContains('ROLE_USER', $user->roles());
    }

    public function testRegisterUserWithDuplicateEmailThrowsException(): void
    {
        $this->handler->__invoke(new RegisterUserCommand(
            firstName: 'First',
            lastName: 'User',
            email: 'dupe@example.com',
            username: 'firstuser',
            plainPassword: 'pass123',
        ));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Email already in use: dupe@example.com');

        $this->handler->__invoke(new RegisterUserCommand(
            firstName: 'Second',
            lastName: 'User',
            email: 'dupe@example.com',
            username: 'seconduser',
            plainPassword: 'pass456',
        ));
    }

    public function testRegisterUserDispatchesEvent(): void
    {
        $this->handler->__invoke(new RegisterUserCommand(
            firstName: 'Jane',
            lastName: 'Doe',
            email: 'jane@example.com',
            username: 'janedoe',
            plainPassword: 'secret123',
        ));

        $this->assertCount(1, $this->eventBus->messages);
        $this->assertInstanceOf(UserRegistered::class, $this->eventBus->messages[0]);
    }
}

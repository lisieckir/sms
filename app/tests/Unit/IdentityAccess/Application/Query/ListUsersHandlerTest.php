<?php

declare(strict_types=1);

namespace App\Tests\Unit\IdentityAccess\Application\Query;

use App\IdentityAccess\Application\Query\ListUsersHandler;
use App\IdentityAccess\Application\Query\ListUsersQuery;
use App\IdentityAccess\Domain\Model\User;
use App\IdentityAccess\Domain\Model\UserEmail;
use App\IdentityAccess\Domain\Model\UserId;
use App\IdentityAccess\Domain\Model\UserName;
use App\IdentityAccess\Domain\Model\UserPassword;
use App\Tests\InMemory\InMemoryPasswordHasher;
use App\Tests\InMemory\InMemoryUserRepository;
use PHPUnit\Framework\TestCase;

class ListUsersHandlerTest extends TestCase
{
    private InMemoryUserRepository $userRepository;
    private ListUsersHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = new InMemoryUserRepository();
        $this->handler = new ListUsersHandler(
            $this->userRepository,
        );
    }

    public function testListUsersReturnsAllUsers(): void
    {
        $this->createUser('alice');
        $this->createUser('bob');

        $users = $this->handler->__invoke(new ListUsersQuery());

        $this->assertCount(2, $users);
        $this->assertContainsOnlyInstancesOf(User::class, $users);
    }

    public function testListUsersEmptyWhenNoUsers(): void
    {
        $users = $this->handler->__invoke(new ListUsersQuery());
        $this->assertEmpty($users);
    }

    public function testListUsersReturnsUserProperties(): void
    {
        $this->createUser('charlie');

        $users = $this->handler->__invoke(new ListUsersQuery());

        $this->assertSame('charlie', $users[0]->username());
        $this->assertTrue($users[0]->isActive());
        $this->assertFalse($users[0]->isAdmin());
    }

    private function createUser(string $username): User
    {
        $user = User::register(
            UserId::generate(),
            new UserName('First', 'Last'),
            new UserEmail($username . '@example.com'),
            $username,
            new UserPassword('hashed-pass'),
            new InMemoryPasswordHasher(),
        );
        $user->releaseEvents();
        $this->userRepository->save($user);
        return $user;
    }
}

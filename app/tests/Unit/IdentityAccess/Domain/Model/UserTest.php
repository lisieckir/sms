<?php

declare(strict_types=1);

namespace App\Tests\Unit\IdentityAccess\Domain\Model;

use App\IdentityAccess\Domain\Model\User;
use App\IdentityAccess\Domain\Model\UserEmail;
use App\IdentityAccess\Domain\Model\UserId;
use App\IdentityAccess\Domain\Model\UserName;
use App\IdentityAccess\Domain\Model\UserPassword;
use App\IdentityAccess\Domain\Event\UserRegistered;
use App\IdentityAccess\Domain\Event\UserPasswordChanged;
use App\Tests\InMemory\InMemoryPasswordHasher;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private InMemoryPasswordHasher $hasher;

    protected function setUp(): void
    {
        $this->hasher = new InMemoryPasswordHasher();
    }

    public function testRegister(): void
    {
        $id = UserId::generate();
        $name = new UserName('John', 'Doe');
        $email = new UserEmail('john@example.com');
        $password = new UserPassword('secret123');

        $user = User::register($id, $name, $email, 'johndoe', $password, $this->hasher);

        $this->assertTrue($id->equals($user->id()));
        $this->assertSame('John Doe', $user->name()->fullName());
        $this->assertSame('john@example.com', $user->email()->value());
        $this->assertSame('johndoe', $user->username());
        $this->assertNotSame('secret123', $user->password()->hashedValue());
        $this->assertSame(['ROLE_USER'], $user->roles());
        $this->assertTrue($user->isActive());
        $this->assertFalse($user->isAdmin());

        $events = $user->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserRegistered::class, $events[0]);
    }

    public function testRegisterAdmin(): void
    {
        $id = UserId::generate();
        $name = new UserName('Admin', 'User');
        $email = new UserEmail('admin@example.com');
        $password = new UserPassword('admin123');

        $user = User::registerAdmin($id, $name, $email, 'admin', $password, $this->hasher);

        $this->assertTrue($user->isAdmin());
        $this->assertContains('ROLE_ADMIN', $user->roles());
    }

    public function testChangePasswordWithCorrectOldPassword(): void
    {
        $user = $this->createUser();
        $oldPassword = new UserPassword('secret123');
        $newPassword = new UserPassword('newsecret');

        $user->changePassword($oldPassword, $newPassword, $this->hasher);

        $this->assertTrue($this->hasher->verify($user->password(), 'newsecret'));

        $events = $user->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserPasswordChanged::class, $events[0]);
    }

    public function testChangePasswordWithWrongOldPasswordThrowsException(): void
    {
        $user = $this->createUser();
        $wrong = new UserPassword('wrongpassword');
        $new = new UserPassword('newsecret');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Current password is incorrect');
        $user->changePassword($wrong, $new, $this->hasher);
    }

    public function testAdminChangePassword(): void
    {
        $user = $this->createUser();
        $newPassword = new UserPassword('adminforced');

        $user->adminChangePassword($newPassword, $this->hasher);

        $this->assertTrue($this->hasher->verify($user->password(), 'adminforced'));
    }

    public function testDeactivate(): void
    {
        $user = $this->createUser();
        $this->assertTrue($user->isActive());

        $user->deactivate();
        $this->assertFalse($user->isActive());
        $this->assertSame('inactive', $user->status());
    }

    public function testActivate(): void
    {
        $user = $this->createUser();
        $user->deactivate();
        $this->assertFalse($user->isActive());

        $user->activate();
        $this->assertTrue($user->isActive());
    }

    public function testChangeEmail(): void
    {
        $user = $this->createUser();
        $newEmail = new UserEmail('new@example.com');

        $user->changeEmail($newEmail);
        $this->assertSame('new@example.com', $user->email()->value());
    }

    public function testSetRoles(): void
    {
        $user = $this->createUser();
        $user->setRoles(['ROLE_ADMIN', 'ROLE_USER']);

        $this->assertTrue($user->isAdmin());
        $this->assertContains('ROLE_ADMIN', $user->roles());
    }

    private function createUser(): User
    {
        $id = UserId::generate();
        $name = new UserName('Test', 'User');
        $email = new UserEmail('test@example.com');
        $password = new UserPassword('secret123');

        $user = User::register($id, $name, $email, 'testuser', $password, $this->hasher);
        $user->releaseEvents();
        return $user;
    }
}

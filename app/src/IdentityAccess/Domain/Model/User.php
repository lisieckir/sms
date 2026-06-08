<?php

declare(strict_types=1);

namespace App\IdentityAccess\Domain\Model;

use App\IdentityAccess\Domain\Event\Trait\EventRecordingCapabilities;
use App\IdentityAccess\Domain\Event\UserPasswordChanged;
use App\IdentityAccess\Domain\Event\UserRegistered;
use App\IdentityAccess\Domain\Service\PasswordHasherInterface;

class User
{
    use EventRecordingCapabilities;

    private \DateTimeImmutable $updatedAt;

    private function __construct(
        private UserId $id,
        private UserName $name,
        private UserEmail $email,
        private string $username,
        private UserPassword $password,
        private array $roles,
        private string $status,
        private \DateTimeImmutable $createdAt,
    ) {
        $this->updatedAt = $createdAt;
    }

    public static function register(
        UserId $id,
        UserName $name,
        UserEmail $email,
        string $username,
        UserPassword $password,
        PasswordHasherInterface $passwordHasher,
    ): self {
        $hashed = $passwordHasher->hash($password->hashedValue());
        $user = new self(
            $id,
            $name,
            $email,
            $username,
            $hashed,
            ['ROLE_USER'],
            'active',
            new \DateTimeImmutable(),
        );
        $user->recordEvent(new UserRegistered($id, $email->value(), $username, new \DateTimeImmutable()));
        return $user;
    }

    public static function registerAdmin(
        UserId $id,
        UserName $name,
        UserEmail $email,
        string $username,
        UserPassword $password,
        PasswordHasherInterface $passwordHasher,
    ): self {
        $hashed = $passwordHasher->hash($password->hashedValue());
        $user = new self(
            $id,
            $name,
            $email,
            $username,
            $hashed,
            ['ROLE_ADMIN', 'ROLE_USER'],
            'active',
            new \DateTimeImmutable(),
        );
        $user->recordEvent(new UserRegistered($id, $email->value(), $username, new \DateTimeImmutable()));
        return $user;
    }

    public function changePassword(UserPassword $oldPassword, UserPassword $newPassword, PasswordHasherInterface $passwordHasher): void
    {
        if (!$passwordHasher->verify($this->password, $oldPassword->hashedValue())) {
            throw new \InvalidArgumentException('Current password is incorrect');
        }
        $this->password = $passwordHasher->hash($newPassword->hashedValue());
        $this->updatedAt = new \DateTimeImmutable();
        $this->recordEvent(new UserPasswordChanged($this->id, new \DateTimeImmutable()));
    }

    public function adminChangePassword(UserPassword $newPassword, PasswordHasherInterface $passwordHasher): void
    {
        $this->password = $passwordHasher->hash($newPassword->hashedValue());
        $this->updatedAt = new \DateTimeImmutable();
        $this->recordEvent(new UserPasswordChanged($this->id, new \DateTimeImmutable()));
    }

    public function deactivate(): void
    {
        $this->status = 'inactive';
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function activate(): void
    {
        $this->status = 'active';
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function changeEmail(UserEmail $email): void
    {
        $this->email = $email;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function name(): UserName
    {
        return $this->name;
    }

    public function email(): UserEmail
    {
        return $this->email;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function password(): UserPassword
    {
        return $this->password;
    }

    public function roles(): array
    {
        return $this->roles;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->roles, true);
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\InMemory;

use App\IdentityAccess\Domain\Model\User;
use App\IdentityAccess\Domain\Model\UserEmail;
use App\IdentityAccess\Domain\Model\UserId;
use App\IdentityAccess\Domain\Model\UserRepositoryInterface;

class InMemoryUserRepository implements UserRepositoryInterface
{
    /** @var array<string, User> */
    private array $users = [];

    public function save(User $user): void
    {
        $this->users[$user->id()->value()] = $user;
    }

    public function findById(UserId $id): ?User
    {
        return $this->users[$id->value()] ?? null;
    }

    public function findByEmail(UserEmail $email): ?User
    {
        foreach ($this->users as $user) {
            if ($user->email()->value() === $email->value()) {
                return $user;
            }
        }
        return null;
    }

    public function findByUsername(string $username): ?User
    {
        foreach ($this->users as $user) {
            if ($user->username() === $username) {
                return $user;
            }
        }
        return null;
    }

    public function findAll(): array
    {
        return array_values($this->users);
    }

    public function searchByTerm(string $term): array
    {
        $term = strtolower($term);
        return array_values(array_filter($this->users, fn(User $user) =>
            str_contains(strtolower($user->name()->firstName()), $term) ||
            str_contains(strtolower($user->name()->lastName()), $term) ||
            str_contains(strtolower($user->username()), $term)
        ));
    }

    public function nextIdentity(): UserId
    {
        return UserId::generate();
    }
}

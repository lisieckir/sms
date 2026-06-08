<?php

declare(strict_types=1);

namespace App\IdentityAccess\Domain\Model;

interface UserRepositoryInterface
{
    public function save(User $user): void;
    public function findById(UserId $id): ?User;
    public function findByEmail(UserEmail $email): ?User;
    public function findByUsername(string $username): ?User;
    public function findAll(): array;
    /** @return User[] */
    public function searchByTerm(string $term): array;
    public function nextIdentity(): UserId;
}

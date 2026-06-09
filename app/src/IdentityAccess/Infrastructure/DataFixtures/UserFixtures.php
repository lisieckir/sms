<?php

declare(strict_types=1);

namespace App\IdentityAccess\Infrastructure\DataFixtures;

use App\IdentityAccess\Domain\Model\User;
use App\IdentityAccess\Domain\Model\UserEmail;
use App\IdentityAccess\Domain\Model\UserId;
use App\IdentityAccess\Domain\Model\UserName;
use App\IdentityAccess\Domain\Model\UserPassword;
use App\IdentityAccess\Domain\Model\UserRepositoryInterface;
use App\IdentityAccess\Domain\Service\PasswordHasherInterface;

final class UserFixtures
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
    ) {}

    public function load(): void
    {
        $users = [
            ['id' => 'admin', 'firstName' => 'Admin', 'lastName' => 'User', 'email' => 'admin@example.com', 'username' => 'admin', 'password' => 'admin123', 'method' => 'registerAdmin'],
            ['id' => 'alice', 'firstName' => 'Alice', 'lastName' => 'Johnson', 'email' => 'alice@example.com', 'username' => 'alice', 'password' => 'user1234', 'method' => 'register'],
            ['id' => 'bob', 'firstName' => 'Bob', 'lastName' => 'Smith', 'email' => 'bob@example.com', 'username' => 'bob', 'password' => 'user1234', 'method' => 'register'],
            ['id' => 'carol', 'firstName' => 'Carol', 'lastName' => 'Williams', 'email' => 'carol@example.com', 'username' => 'carol', 'password' => 'user1234', 'method' => 'register'],
            ['id' => 'dave', 'firstName' => 'Dave', 'lastName' => 'Brown', 'email' => 'dave@example.com', 'username' => 'dave', 'password' => 'user1234', 'method' => 'register'],
        ];

        foreach ($users as $data) {
            if ($this->userRepository->findByUsername($data['username']) !== null) {
                continue;
            }

            $id = UserId::generate();
            $name = new UserName($data['firstName'], $data['lastName']);
            $email = new UserEmail($data['email']);
            $password = new UserPassword($data['password']);

            $user = match ($data['method']) {
                'registerAdmin' => User::registerAdmin($id, $name, $email, $data['username'], $password, $this->passwordHasher),
                'register' => User::register($id, $name, $email, $data['username'], $password, $this->passwordHasher),
            };

            $this->userRepository->save($user);
        }
    }
}

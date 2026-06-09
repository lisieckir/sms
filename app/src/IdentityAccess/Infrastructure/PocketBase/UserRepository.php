<?php

declare(strict_types=1);

namespace App\IdentityAccess\Infrastructure\PocketBase;

use App\Core\Infrastructure\PocketBase\PocketBaseClient;
use App\IdentityAccess\Domain\Model\User;
use App\IdentityAccess\Domain\Model\UserEmail;
use App\IdentityAccess\Domain\Model\UserId;
use App\IdentityAccess\Domain\Model\UserName;
use App\IdentityAccess\Domain\Model\UserPassword;
use App\IdentityAccess\Domain\Model\UserRepositoryInterface;

final readonly class UserRepository implements UserRepositoryInterface
{
    private const COLLECTION = 'app_users';

    public function __construct(
        private PocketBaseClient $pb,
    ) {}

    public function save(User $user): void
    {
        $data = [
            'userId' => $user->id()->value(),
            'firstName' => $user->name()->firstName(),
            'lastName' => $user->name()->lastName(),
            'email' => $user->email()->value(),
            'username' => $user->username(),
            'password' => $user->password()->hashedValue(),
            'roles' => $user->roles(),
            'status' => $user->status(),
            'created' => $user->createdAt()->format('c'),
            'updated' => $user->updatedAt()->format('c'),
        ];

        $this->pb->upsert(self::COLLECTION, sprintf('userId="%s"', $user->id()->value()), $data);
    }

    public function findById(UserId $id): ?User
    {
        $record = $this->pb->first(self::COLLECTION, sprintf('userId="%s"', $id->value()));
        return $record !== null ? $this->toDomain($record) : null;
    }

    public function findByEmail(UserEmail $email): ?User
    {
        $record = $this->pb->first(self::COLLECTION, sprintf('email="%s"', $email->value()));
        return $record !== null ? $this->toDomain($record) : null;
    }

    public function findByUsername(string $username): ?User
    {
        $record = $this->pb->first(self::COLLECTION, sprintf('username="%s"', $username));
        return $record !== null ? $this->toDomain($record) : null;
    }

    public function findAll(): array
    {
        $result = $this->pb->list(self::COLLECTION);
        return array_map(fn(array $r) => $this->toDomain($r), $result['items']);
    }

    public function searchByTerm(string $term): array
    {
        $escaped = addslashes($term);
        $filter = sprintf('firstName~"%s" || lastName~"%s" || username~"%s"', $escaped, $escaped, $escaped);
        $result = $this->pb->list(self::COLLECTION, ['filter' => $filter]);
        return array_map(fn(array $r) => $this->toDomain($r), $result['items']);
    }

    public function nextIdentity(): UserId
    {
        return UserId::generate();
    }

    private function toDomain(array $record): User
    {
        $fields = $record;

        $user = new \ReflectionClass(User::class);
        $instance = $user->newInstanceWithoutConstructor();

        $idProp = $user->getProperty('id');
        $idProp->setAccessible(true);
        $idProp->setValue($instance, new UserId($fields['userId']));

        $nameProp = $user->getProperty('name');
        $nameProp->setAccessible(true);
        $nameProp->setValue($instance, new UserName($fields['firstName'], $fields['lastName']));

        $emailProp = $user->getProperty('email');
        $emailProp->setAccessible(true);
        $emailProp->setValue($instance, new UserEmail($fields['email']));

        $usernameProp = $user->getProperty('username');
        $usernameProp->setAccessible(true);
        $usernameProp->setValue($instance, $fields['username']);

        $passwordProp = $user->getProperty('password');
        $passwordProp->setAccessible(true);
        $passwordProp->setValue($instance, new UserPassword($fields['password']));

        $rolesProp = $user->getProperty('roles');
        $rolesProp->setAccessible(true);
        $rolesProp->setValue($instance, $fields['roles'] ?? []);

        $statusProp = $user->getProperty('status');
        $statusProp->setAccessible(true);
        $statusProp->setValue($instance, $fields['status']);

        $createdProp = $user->getProperty('createdAt');
        $createdProp->setAccessible(true);
        $createdProp->setValue($instance, new \DateTimeImmutable($fields['created']));

        $updatedProp = $user->getProperty('updatedAt');
        $updatedProp->setAccessible(true);
        $updatedProp->setValue($instance, new \DateTimeImmutable($fields['updated']));

        return $instance;
    }
}

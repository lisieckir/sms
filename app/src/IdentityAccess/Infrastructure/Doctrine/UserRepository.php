<?php

declare(strict_types=1);

namespace App\IdentityAccess\Infrastructure\Doctrine;

use App\IdentityAccess\Domain\Model\User;
use App\IdentityAccess\Domain\Model\UserEmail;
use App\IdentityAccess\Domain\Model\UserId;
use App\IdentityAccess\Domain\Model\UserName;
use App\IdentityAccess\Domain\Model\UserPassword;
use App\IdentityAccess\Domain\Model\UserRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

class UserRepository implements UserRepositoryInterface
{
    private DocumentRepository $repository;

    public function __construct(
        private DocumentManager $dm,
    ) {
        $this->repository = $dm->getRepository(UserDocument::class);
    }

    public function save(User $user): void
    {
        $doc = $this->repository->findOneBy(['userId' => $user->id()->value()]);
        if ($doc === null) {
            $doc = new UserDocument();
            $doc->userId = $user->id()->value();
            $doc->createdAt = \DateTime::createFromImmutable($user->createdAt());
            $this->dm->persist($doc);
        }

        $doc->firstName = $user->name()->firstName();
        $doc->lastName = $user->name()->lastName();
        $doc->email = $user->email()->value();
        $doc->username = $user->username();
        $doc->password = $user->password()->hashedValue();
        $doc->roles = $user->roles();
        $doc->status = $user->status();
        $doc->updatedAt = \DateTime::createFromImmutable($user->updatedAt());

        $this->dm->flush();
    }

    public function findById(UserId $id): ?User
    {
        $doc = $this->repository->findOneBy(['userId' => $id->value()]);
        return $doc ? $this->toDomain($doc) : null;
    }

    public function findByEmail(UserEmail $email): ?User
    {
        $doc = $this->repository->findOneBy(['email' => $email->value()]);
        return $doc ? $this->toDomain($doc) : null;
    }

    public function findByUsername(string $username): ?User
    {
        $doc = $this->repository->findOneBy(['username' => $username]);
        return $doc ? $this->toDomain($doc) : null;
    }

    public function findAll(): array
    {
        $docs = $this->repository->findAll();
        return array_map(fn(UserDocument $doc) => $this->toDomain($doc), $docs);
    }

    public function searchByTerm(string $term): array
    {
        $regex = new \MongoDB\BSON\Regex(preg_quote($term), 'i');
        $qb = $this->repository->createQueryBuilder();
        $qb->addOr($qb->expr()->field('firstName')->equals($regex));
        $qb->addOr($qb->expr()->field('lastName')->equals($regex));
        $qb->addOr($qb->expr()->field('username')->equals($regex));
        $docs = $qb->getQuery()->execute()->toArray();
        return array_map(fn(UserDocument $doc) => $this->toDomain($doc), $docs);
    }

    public function nextIdentity(): UserId
    {
        return UserId::generate();
    }

    private function toDomain(UserDocument $doc): User
    {
        $user = new \ReflectionClass(User::class);
        $instance = $user->newInstanceWithoutConstructor();

        $idProp = $user->getProperty('id');
        $idProp->setAccessible(true);
        $idProp->setValue($instance, new UserId($doc->userId));

        $nameProp = $user->getProperty('name');
        $nameProp->setAccessible(true);
        $nameProp->setValue($instance, new UserName($doc->firstName, $doc->lastName));

        $emailProp = $user->getProperty('email');
        $emailProp->setAccessible(true);
        $emailProp->setValue($instance, new UserEmail($doc->email));

        $usernameProp = $user->getProperty('username');
        $usernameProp->setAccessible(true);
        $usernameProp->setValue($instance, $doc->username);

        $passwordProp = $user->getProperty('password');
        $passwordProp->setAccessible(true);
        $passwordProp->setValue($instance, new UserPassword($doc->password));

        $rolesProp = $user->getProperty('roles');
        $rolesProp->setAccessible(true);
        $rolesProp->setValue($instance, $doc->roles);

        $statusProp = $user->getProperty('status');
        $statusProp->setAccessible(true);
        $statusProp->setValue($instance, $doc->status);

        $createdProp = $user->getProperty('createdAt');
        $createdProp->setAccessible(true);
        $createdProp->setValue($instance, \DateTimeImmutable::createFromMutable($doc->createdAt));

        $updatedProp = $user->getProperty('updatedAt');
        $updatedProp->setAccessible(true);
        $updatedProp->setValue($instance, \DateTimeImmutable::createFromMutable($doc->updatedAt));

        return $instance;
    }
}

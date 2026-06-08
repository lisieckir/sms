<?php

declare(strict_types=1);

namespace App\IdentityAccess\Infrastructure\Security;

use App\IdentityAccess\Domain\Model\UserPassword;
use App\IdentityAccess\Domain\Service\PasswordHasherInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface as SymfonyPasswordHasherInterface;

final readonly class SymfonyPasswordHasher implements PasswordHasherInterface
{
    private SymfonyPasswordHasherInterface $hasher;

    public function __construct(
        PasswordHasherFactoryInterface $hasherFactory,
    ) {
        $this->hasher = $hasherFactory->getPasswordHasher(UserIdentity::class);
    }

    public function hash(string $plainPassword): UserPassword
    {
        return new UserPassword($this->hasher->hash($plainPassword));
    }

    public function verify(UserPassword $hashed, string $plainPassword): bool
    {
        return $this->hasher->verify($hashed->hashedValue(), $plainPassword);
    }
}

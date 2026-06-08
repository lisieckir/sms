<?php

declare(strict_types=1);

namespace App\Tests\InMemory;

use App\IdentityAccess\Domain\Model\UserPassword;
use App\IdentityAccess\Domain\Service\PasswordHasherInterface;

class InMemoryPasswordHasher implements PasswordHasherInterface
{
    public function hash(string $plainPassword): UserPassword
    {
        return new UserPassword($plainPassword . '$hashed');
    }

    public function verify(UserPassword $hashed, string $plainPassword): bool
    {
        return $hashed->hashedValue() === $plainPassword . '$hashed';
    }
}

<?php

declare(strict_types=1);

namespace App\IdentityAccess\Domain\Service;

use App\IdentityAccess\Domain\Model\UserPassword;

interface PasswordHasherInterface
{
    public function hash(string $plainPassword): UserPassword;
    public function verify(UserPassword $hashed, string $plainPassword): bool;
}

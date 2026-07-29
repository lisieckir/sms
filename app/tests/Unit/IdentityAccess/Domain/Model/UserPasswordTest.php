<?php

declare(strict_types=1);

namespace App\Tests\Unit\IdentityAccess\Domain\Model;

use App\IdentityAccess\Domain\Model\UserPassword;
use PHPUnit\Framework\TestCase;

class UserPasswordTest extends TestCase
{
    public function testCreate(): void
    {
        $password = new UserPassword('hashed_value');
        $this->assertSame('hashed_value', $password->hashedValue());
        $this->assertSame('hashed_value', (string) $password);
    }

    public function testCreateWithEmptyString(): void
    {
        $password = new UserPassword('');
        $this->assertSame('', $password->hashedValue());
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\IdentityAccess\Domain\Model;

use App\IdentityAccess\Domain\Model\UserName;
use PHPUnit\Framework\TestCase;

class UserNameTest extends TestCase
{
    public function testCreate(): void
    {
        $name = new UserName('John', 'Doe');
        $this->assertSame('John', $name->firstName());
        $this->assertSame('Doe', $name->lastName());
        $this->assertSame('John Doe', $name->fullName());
    }

    public function testCreateWithWhitespaceOnlyThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be empty');
        new UserName('  ', 'Doe');
    }

    public function testCreateWithEmptyLastNameThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new UserName('John', '');
    }
}

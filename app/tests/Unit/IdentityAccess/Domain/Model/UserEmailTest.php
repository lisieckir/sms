<?php

declare(strict_types=1);

namespace App\Tests\Unit\IdentityAccess\Domain\Model;

use App\IdentityAccess\Domain\Model\UserEmail;
use PHPUnit\Framework\TestCase;

class UserEmailTest extends TestCase
{
    public function testCreateWithValidEmail(): void
    {
        $email = new UserEmail('test@example.com');
        $this->assertSame('test@example.com', $email->value());
        $this->assertSame('test@example.com', (string) $email);
    }

    public function testCreateWithInvalidEmailThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email');
        new UserEmail('not-an-email');
    }

    public function testCreateWithEmptyStringThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new UserEmail('');
    }

    public function testEquality(): void
    {
        $a = new UserEmail('test@example.com');
        $b = new UserEmail('test@example.com');
        $c = new UserEmail('other@example.com');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }
}

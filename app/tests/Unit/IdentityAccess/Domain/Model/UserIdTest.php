<?php

declare(strict_types=1);

namespace App\Tests\Unit\IdentityAccess\Domain\Model;

use App\IdentityAccess\Domain\Model\UserId;
use PHPUnit\Framework\TestCase;

class UserIdTest extends TestCase
{
    public function testCreateFromValidUuid(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $id = new UserId($uuid);
        $this->assertSame($uuid, $id->value());
        $this->assertSame($uuid, (string) $id);
    }

    public function testGenerateReturnsValidUuid(): void
    {
        $id = UserId::generate();
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $id->value());
    }

    public function testCreateWithInvalidUuidThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new UserId('not-a-uuid');
    }

    public function testEquality(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $a = new UserId($uuid);
        $b = new UserId($uuid);
        $c = new UserId('550e8400-e29b-41d4-a716-446655440001');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }
}

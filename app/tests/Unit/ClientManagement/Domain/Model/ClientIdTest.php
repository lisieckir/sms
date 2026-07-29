<?php

declare(strict_types=1);

namespace App\Tests\Unit\ClientManagement\Domain\Model;

use App\ClientManagement\Domain\Model\ClientId;
use PHPUnit\Framework\TestCase;

class ClientIdTest extends TestCase
{
    public function testCreateFromValidUuid(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $id = new ClientId($uuid);
        $this->assertSame($uuid, $id->value());
        $this->assertSame($uuid, (string) $id);
    }

    public function testGenerateReturnsValidUuid(): void
    {
        $id = ClientId::generate();
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $id->value());
    }

    public function testCreateWithInvalidUuidThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ClientId('not-a-uuid');
    }

    public function testEquality(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $a = new ClientId($uuid);
        $b = new ClientId($uuid);
        $c = new ClientId('550e8400-e29b-41d4-a716-446655440001');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }
}

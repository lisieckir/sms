<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskManagement\Domain\Model;

use App\TaskManagement\Domain\Model\TaskId;
use PHPUnit\Framework\TestCase;

class TaskIdTest extends TestCase
{
    public function testCreateFromValidUuid(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $id = new TaskId($uuid);
        $this->assertSame($uuid, $id->value());
        $this->assertSame($uuid, (string) $id);
    }

    public function testGenerateReturnsValidUuid(): void
    {
        $id = TaskId::generate();
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $id->value());
    }

    public function testCreateWithInvalidUuidThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TaskId('not-a-uuid');
    }

    public function testEquality(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $a = new TaskId($uuid);
        $b = new TaskId($uuid);
        $c = new TaskId('550e8400-e29b-41d4-a716-446655440001');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }
}

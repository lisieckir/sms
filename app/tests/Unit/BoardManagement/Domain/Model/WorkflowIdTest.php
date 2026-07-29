<?php

declare(strict_types=1);

namespace App\Tests\Unit\BoardManagement\Domain\Model;

use App\BoardManagement\Domain\Model\WorkflowId;
use PHPUnit\Framework\TestCase;

class WorkflowIdTest extends TestCase
{
    public function testCreateFromValidUuid(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $id = new WorkflowId($uuid);
        $this->assertSame($uuid, $id->value());
        $this->assertSame($uuid, (string) $id);
    }

    public function testGenerateReturnsValidUuid(): void
    {
        $id = WorkflowId::generate();
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $id->value());
    }

    public function testCreateWithInvalidUuidThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new WorkflowId('not-a-uuid');
    }

    public function testEquality(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $a = new WorkflowId($uuid);
        $b = new WorkflowId($uuid);
        $c = new WorkflowId('550e8400-e29b-41d4-a716-446655440001');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskManagement\Domain\Model;

use App\TaskManagement\Domain\Model\TaskPriority;
use PHPUnit\Framework\TestCase;

class TaskPriorityTest extends TestCase
{
    public function testCreateWithValidValues(): void
    {
        foreach (['low', 'medium', 'high', 'critical'] as $valid) {
            $p = new TaskPriority($valid);
            $this->assertSame($valid, $p->value());
            $this->assertSame($valid, (string) $p);
        }
    }

    public function testCreateWithInvalidValueThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TaskPriority('urgent');
    }

    public function testImmutability(): void
    {
        $p = new TaskPriority('medium');
        $ref = new \ReflectionClass($p);
        $this->assertTrue($ref->isReadOnly());
    }
}

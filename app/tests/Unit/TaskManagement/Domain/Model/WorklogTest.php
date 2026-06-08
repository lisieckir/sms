<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskManagement\Domain\Model;

use App\TaskManagement\Domain\Model\Worklog;
use PHPUnit\Framework\TestCase;

class WorklogTest extends TestCase
{
    public function testCreateWithDefaultDate(): void
    {
        $worklog = Worklog::create('user-1', 60, 'Fixed bug');
        $this->assertNotEmpty($worklog->id());
        $this->assertSame('user-1', $worklog->userId());
        $this->assertSame(60, $worklog->minutes());
        $this->assertSame('Fixed bug', $worklog->description());
        $this->assertInstanceOf(\DateTimeImmutable::class, $worklog->date());
        $this->assertInstanceOf(\DateTimeImmutable::class, $worklog->createdAt());
    }

    public function testCreateWithCustomDate(): void
    {
        $date = new \DateTimeImmutable('2026-01-15');
        $worklog = Worklog::create('user-1', 30, 'Work', $date);
        $this->assertSame($date, $worklog->date());
    }

    public function testCreateWithZeroMinutesThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Worklog minutes must be positive');
        Worklog::create('user-1', 0, 'test');
    }

    public function testCreateWithNegativeMinutesThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Worklog::create('user-1', -1, 'test');
    }

    public function testImmutability(): void
    {
        $worklog = Worklog::create('user-1', 1, 'test');
        $ref = new \ReflectionClass($worklog);
        $this->assertTrue($ref->isReadOnly());
    }
}

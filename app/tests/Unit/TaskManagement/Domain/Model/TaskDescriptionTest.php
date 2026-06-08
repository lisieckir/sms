<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskManagement\Domain\Model;

use App\TaskManagement\Domain\Model\TaskDescription;
use PHPUnit\Framework\TestCase;

class TaskDescriptionTest extends TestCase
{
    public function testCreateWithValidValue(): void
    {
        $desc = new TaskDescription('Valid description');
        $this->assertSame('Valid description', $desc->value());
        $this->assertSame('Valid description', (string) $desc);
    }

    public function testCreateWithEmptyString(): void
    {
        $desc = new TaskDescription('');
        $this->assertSame('', $desc->value());
    }

    public function testCreateWithExactly5000Chars(): void
    {
        $value = str_repeat('a', 5000);
        $desc = new TaskDescription($value);
        $this->assertSame(5000, strlen($desc->value()));
    }

    public function testCreateWithOver5000CharsThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Description too long');
        new TaskDescription(str_repeat('a', 5001));
    }

    public function testImmutability(): void
    {
        $desc = new TaskDescription('original');
        $ref = new \ReflectionClass($desc);
        $this->assertTrue($ref->isReadOnly());
    }
}

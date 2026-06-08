<?php

declare(strict_types=1);

namespace App\Tests\Unit\BoardManagement\Domain\Model;

use App\BoardManagement\Domain\Model\Stage;
use PHPUnit\Framework\TestCase;

class StageTest extends TestCase
{
    public function testCreate(): void
    {
        $stage = Stage::create('To Do', 0);
        $this->assertNotEmpty($stage->id());
        $this->assertSame('To Do', $stage->name());
        $this->assertSame(0, $stage->position());
    }

    public function testCreateWithEmptyNameThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Stage name cannot be empty');
        Stage::create('', 0);
    }

    public function testRenameReturnsNewInstance(): void
    {
        $stage = Stage::create('To Do', 0);
        $renamed = $stage->rename('Backlog');

        $this->assertSame('To Do', $stage->name());
        $this->assertSame('Backlog', $renamed->name());
        $this->assertSame($stage->id(), $renamed->id());
        $this->assertSame($stage->position(), $renamed->position());
    }

    public function testMoveReturnsNewInstance(): void
    {
        $stage = Stage::create('To Do', 0);
        $moved = $stage->move(2);

        $this->assertSame(0, $stage->position());
        $this->assertSame(2, $moved->position());
        $this->assertSame($stage->id(), $moved->id());
        $this->assertSame($stage->name(), $moved->name());
    }

    public function testImmutability(): void
    {
        $stage = Stage::create('Test', 0);
        $ref = new \ReflectionClass($stage);
        $this->assertTrue($ref->isReadOnly());
    }
}

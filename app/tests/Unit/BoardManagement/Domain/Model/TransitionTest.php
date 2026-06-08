<?php

declare(strict_types=1);

namespace App\Tests\Unit\BoardManagement\Domain\Model;

use App\BoardManagement\Domain\Model\Transition;
use PHPUnit\Framework\TestCase;

class TransitionTest extends TestCase
{
    public function testCreate(): void
    {
        $t = Transition::create('stage-a', 'stage-b');
        $this->assertNotEmpty($t->id());
        $this->assertSame('stage-a', $t->fromStageId());
        $this->assertSame('stage-b', $t->toStageId());
    }

    public function testCreateWithSameFromAndToThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Transition must be between different stages');
        Transition::create('same-stage', 'same-stage');
    }

    public function testMatches(): void
    {
        $t = Transition::create('a', 'b');
        $this->assertTrue($t->matches('a', 'b'));
        $this->assertFalse($t->matches('a', 'c'));
        $this->assertFalse($t->matches('b', 'a'));
    }

    public function testImmutability(): void
    {
        $t = Transition::create('a', 'b');
        $ref = new \ReflectionClass($t);
        $this->assertTrue($ref->isReadOnly());
    }
}

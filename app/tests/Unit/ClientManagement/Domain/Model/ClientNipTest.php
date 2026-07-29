<?php

declare(strict_types=1);

namespace App\Tests\Unit\ClientManagement\Domain\Model;

use App\ClientManagement\Domain\Model\ClientNip;
use PHPUnit\Framework\TestCase;

class ClientNipTest extends TestCase
{
    public function testCreateWithValidNip(): void
    {
        $nip = new ClientNip('1234567890');
        $this->assertSame('1234567890', $nip->value());
        $this->assertSame('1234567890', (string) $nip);
    }

    public function testCreateWithLessThan4CharsThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ClientNip('123');
    }

    public function testCreateWithMoreThan20CharsThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ClientNip('123456789012345678901');
    }

    public function testCreateWithSpacesThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ClientNip('abcd efgh');
    }

    public function testEquality(): void
    {
        $a = new ClientNip('1234567890');
        $b = new ClientNip('1234567890');
        $c = new ClientNip('0987654321');

        $this->assertSame($a->value(), $b->value());
        $this->assertNotSame($a->value(), $c->value());
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskManagement\Domain\Model;

use App\TaskManagement\Domain\Model\Comment;
use PHPUnit\Framework\TestCase;

class CommentTest extends TestCase
{
    public function testCreate(): void
    {
        $comment = Comment::create('user-1', 'Hello world');
        $this->assertNotEmpty($comment->id());
        $this->assertSame('user-1', $comment->userId());
        $this->assertSame('Hello world', $comment->content());
        $this->assertInstanceOf(\DateTimeImmutable::class, $comment->createdAt());
    }

    public function testCreateWithEmptyContentThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Comment content cannot be empty');
        Comment::create('user-1', '');
    }

    public function testImmutability(): void
    {
        $comment = Comment::create('user-1', 'test');
        $ref = new \ReflectionClass($comment);
        $this->assertTrue($ref->isReadOnly());
    }
}

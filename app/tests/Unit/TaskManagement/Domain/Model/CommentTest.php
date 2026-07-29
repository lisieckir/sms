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

    public function testCreateWithMultilineContent(): void
    {
        $content = "Line 1\nLine 2\n\nLine 4";
        $comment = Comment::create('user-1', $content);
        $this->assertSame($content, $comment->content());
        $this->assertStringContainsString("\n", $comment->content());
    }

    public function testWithContentReturnsNewInstance(): void
    {
        $comment = Comment::create('user-1', 'Original');
        $edited = $comment->withContent('Updated');

        $this->assertNotSame($comment, $edited);
        $this->assertSame('Updated', $edited->content());
        $this->assertSame('Original', $comment->content());
        $this->assertSame($comment->id(), $edited->id());
        $this->assertSame($comment->userId(), $edited->userId());
        $this->assertSame($comment->createdAt()->format('c'), $edited->createdAt()->format('c'));
        $this->assertNull($comment->editedAt());
        $this->assertNotNull($edited->editedAt());
    }

    public function testWithContentEmptyThrowsException(): void
    {
        $comment = Comment::create('user-1', 'Original');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Comment content cannot be empty');
        $comment->withContent('');
    }

    public function testImmutability(): void
    {
        $comment = Comment::create('user-1', 'test');
        $ref = new \ReflectionClass($comment);
        $this->assertTrue($ref->isReadOnly());
    }
}

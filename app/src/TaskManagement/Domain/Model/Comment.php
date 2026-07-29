<?php

declare(strict_types=1);

namespace App\TaskManagement\Domain\Model;

use Symfony\Component\Uid\Uuid;

final readonly class Comment
{
    public function __construct(
        private string $id,
        private string $userId,
        private string $content,
        private \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $editedAt = null,
    ) {
        if (empty($content)) {
            throw new \InvalidArgumentException('Comment content cannot be empty');
        }
    }

    public static function create(string $userId, string $content): self
    {
        return new self(
            Uuid::v4()->toRfc4122(),
            $userId,
            $content,
            new \DateTimeImmutable(),
        );
    }

    public function withContent(string $newContent): self
    {
        if (empty($newContent)) {
            throw new \InvalidArgumentException('Comment content cannot be empty');
        }

        return new self(
            $this->id,
            $this->userId,
            $newContent,
            $this->createdAt,
            new \DateTimeImmutable(),
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function editedAt(): ?\DateTimeImmutable
    {
        return $this->editedAt;
    }
}

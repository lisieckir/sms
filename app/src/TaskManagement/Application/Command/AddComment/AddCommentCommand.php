<?php

declare(strict_types=1);

namespace App\TaskManagement\Application\Command\AddComment;

final class AddCommentCommand
{
    public function __construct(
        private string $taskId,
        private string $userId,
        private string $content,
    ) {}

    public function taskId(): string { return $this->taskId; }
    public function userId(): string { return $this->userId; }
    public function content(): string { return $this->content; }
}

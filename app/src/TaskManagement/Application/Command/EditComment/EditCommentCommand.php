<?php

declare(strict_types=1);

namespace App\TaskManagement\Application\Command\EditComment;

final class EditCommentCommand
{
    public function __construct(
        private string $taskId,
        private string $commentId,
        private string $content,
    ) {}

    public function taskId(): string { return $this->taskId; }
    public function commentId(): string { return $this->commentId; }
    public function content(): string { return $this->content; }
}

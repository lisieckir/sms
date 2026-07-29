<?php

declare(strict_types=1);

namespace App\TaskManagement\Application\Command\DeleteComment;

final class DeleteCommentCommand
{
    public function __construct(
        private string $taskId,
        private string $commentId,
    ) {}

    public function taskId(): string { return $this->taskId; }
    public function commentId(): string { return $this->commentId; }
}

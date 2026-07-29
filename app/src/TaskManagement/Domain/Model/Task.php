<?php

declare(strict_types=1);

namespace App\TaskManagement\Domain\Model;

use App\TaskManagement\Domain\Event\CommentAdded;
use App\TaskManagement\Domain\Event\CommentEdited;
use App\TaskManagement\Domain\Event\CommentRemoved;
use App\TaskManagement\Domain\Event\TaskAssigned;
use App\TaskManagement\Domain\Event\TaskClientChanged;
use App\TaskManagement\Domain\Event\TaskCreated;
use App\TaskManagement\Domain\Event\TaskDescriptionChanged;
use App\TaskManagement\Domain\Event\TaskMoved;
use App\TaskManagement\Domain\Event\WorklogAdded;
use App\TaskManagement\Domain\Event\Trait\EventRecordingCapabilities;

class Task
{
    use EventRecordingCapabilities;

    private \DateTimeImmutable $updatedAt;
    private array $comments = [];
    private array $worklogs = [];
    private int $totalTimeSpent = 0;

    private function __construct(
        private TaskId $id,
        private string $title,
        private TaskDescription $description,
        private string $creatorId,
        private ?string $assigneeId,
        private ?string $clientId,
        private string $stageId,
        private int $position,
        private TaskPriority $priority,
        private ?string $parentTaskId,
        private string $status,
        private \DateTimeImmutable $createdAt,
    ) {
        $this->updatedAt = $createdAt;
    }

    public static function create(
        TaskId $id,
        string $title,
        TaskDescription $description,
        string $creatorId,
        string $stageId,
        int $position,
        ?string $assigneeId = null,
        ?string $clientId = null,
        ?string $parentTaskId = null,
        ?TaskPriority $priority = null,
    ): self {
        $priority = $priority ?? new TaskPriority('medium');
        $task = new self(
            $id,
            $title,
            $description,
            $creatorId,
            $assigneeId,
            $clientId,
            $stageId,
            $position,
            $priority,
            $parentTaskId,
            'active',
            new \DateTimeImmutable(),
        );
        $task->recordEvent(new TaskCreated($id, $title, $creatorId, $stageId, $priority, new \DateTimeImmutable()));
        return $task;
    }

    public function moveToStage(string $stageId, int $position): void
    {
        $oldStage = $this->stageId;
        $this->stageId = $stageId;
        $this->position = $position;
        $this->updatedAt = new \DateTimeImmutable();
        $this->recordEvent(new TaskMoved($this->id, $oldStage, $stageId, new \DateTimeImmutable()));
    }

    public function changePosition(int $position): void
    {
        $this->position = $position;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function assignTo(?string $assigneeId): void
    {
        $oldAssignee = $this->assigneeId;
        $this->assigneeId = $assigneeId;
        $this->updatedAt = new \DateTimeImmutable();
        $this->recordEvent(new TaskAssigned($this->id, $oldAssignee, $assigneeId, new \DateTimeImmutable()));
    }

    public function changeClient(?string $clientId): void
    {
        $oldClient = $this->clientId;
        $this->clientId = $clientId;
        $this->updatedAt = new \DateTimeImmutable();
        $this->recordEvent(new TaskClientChanged($this->id, $oldClient, $clientId, new \DateTimeImmutable()));
    }

    public function changeDescription(TaskDescription $newDescription): void
    {
        $oldValue = $this->description->value();
        $this->description = $newDescription;
        $this->updatedAt = new \DateTimeImmutable();
        $this->recordEvent(new TaskDescriptionChanged($this->id, $oldValue, $newDescription->value(), new \DateTimeImmutable()));
    }

    public function addComment(string $userId, string $content): void
    {
        if (count($this->comments) >= 50) {
            throw new \RuntimeException('Maximum of 50 comments per task reached');
        }
        $comment = Comment::create($userId, $content);
        $this->comments[] = $comment;
        $this->updatedAt = new \DateTimeImmutable();
        $this->recordEvent(new CommentAdded($this->id, $comment->id(), $userId, new \DateTimeImmutable()));
    }

    public function editComment(string $commentId, string $newContent): void
    {
        foreach ($this->comments as $i => $comment) {
            if ($comment->id() === $commentId) {
                $oldContent = $comment->content();
                $this->comments[$i] = $comment->withContent($newContent);
                $this->updatedAt = new \DateTimeImmutable();
                $this->recordEvent(new CommentEdited($this->id, $commentId, $comment->userId(), $oldContent, $newContent, new \DateTimeImmutable()));
                return;
            }
        }
        throw new \InvalidArgumentException('Comment not found');
    }

    public function removeComment(string $commentId): void
    {
        foreach ($this->comments as $i => $comment) {
            if ($comment->id() === $commentId) {
                array_splice($this->comments, $i, 1);
                $this->updatedAt = new \DateTimeImmutable();
                $this->recordEvent(new CommentRemoved($this->id, $commentId, $comment->userId(), new \DateTimeImmutable()));
                return;
            }
        }
        throw new \InvalidArgumentException('Comment not found');
    }

    public function addWorklog(string $userId, int $minutes, string $description): void
    {
        $worklog = Worklog::create($userId, $minutes, $description);
        $this->worklogs[] = $worklog;
        $this->totalTimeSpent += $minutes;
        $this->updatedAt = new \DateTimeImmutable();
        $this->recordEvent(new WorklogAdded($this->id, $worklog->id(), $userId, $minutes, new \DateTimeImmutable()));
    }

    public function archive(): void
    {
        $this->status = 'archived';
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function id(): TaskId
    {
        return $this->id;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function description(): TaskDescription
    {
        return $this->description;
    }

    public function creatorId(): string
    {
        return $this->creatorId;
    }

    public function assigneeId(): ?string
    {
        return $this->assigneeId;
    }

    public function clientId(): ?string
    {
        return $this->clientId;
    }

    public function stageId(): string
    {
        return $this->stageId;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function priority(): TaskPriority
    {
        return $this->priority;
    }

    public function parentTaskId(): ?string
    {
        return $this->parentTaskId;
    }

    public function comments(): array
    {
        return $this->comments;
    }

    public function worklogs(): array
    {
        return $this->worklogs;
    }

    public function totalTimeSpent(): int
    {
        return $this->totalTimeSpent;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}

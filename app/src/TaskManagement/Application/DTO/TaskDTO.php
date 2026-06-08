<?php

declare(strict_types=1);

namespace App\TaskManagement\Application\DTO;

final class TaskDTO
{
    public function __construct(
        private string $id,
        private string $title,
        private string $description,
        private string $creatorId,
        private ?string $assigneeId,
        private ?string $assigneeName,
        private ?string $clientId,
        private ?string $clientName,
        private string $stageId,
        private string $stageName,
        private int $position,
        private ?string $parentTaskId,
        private ?string $parentTaskName,
        private int $totalTimeSpent,
        private string $status,
        private array $comments,
        private array $worklogs,
        private array $subtasks,
        private string $createdAt,
        private string $updatedAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['title'],
            $data['description'],
            $data['creatorId'],
            $data['assigneeId'] ?? null,
            $data['assigneeName'] ?? null,
            $data['clientId'] ?? null,
            $data['clientName'] ?? null,
            $data['stageId'],
            $data['stageName'] ?? $data['stageId'],
            $data['position'],
            $data['parentTaskId'] ?? null,
            $data['parentTaskName'] ?? null,
            $data['totalTimeSpent'] ?? 0,
            $data['status'],
            $data['comments'] ?? [],
            $data['worklogs'] ?? [],
            $data['subtasks'] ?? [],
            $data['createdAt'],
            $data['updatedAt'],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'creatorId' => $this->creatorId,
            'assigneeId' => $this->assigneeId,
            'assigneeName' => $this->assigneeName,
            'clientId' => $this->clientId,
            'clientName' => $this->clientName,
            'stageId' => $this->stageId,
            'stageName' => $this->stageName,
            'position' => $this->position,
            'parentTaskId' => $this->parentTaskId,
            'parentTaskName' => $this->parentTaskName,
            'totalTimeSpent' => $this->totalTimeSpent,
            'status' => $this->status,
            'comments' => $this->comments,
            'worklogs' => $this->worklogs,
            'subtasks' => $this->subtasks,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }

    public function id(): string { return $this->id; }
    public function title(): string { return $this->title; }
    public function description(): string { return $this->description; }
    public function creatorId(): string { return $this->creatorId; }
    public function assigneeId(): ?string { return $this->assigneeId; }
    public function assigneeName(): ?string { return $this->assigneeName; }
    public function clientId(): ?string { return $this->clientId; }
    public function clientName(): ?string { return $this->clientName; }
    public function stageId(): string { return $this->stageId; }
    public function stageName(): string { return $this->stageName; }
    public function position(): int { return $this->position; }
    public function parentTaskId(): ?string { return $this->parentTaskId; }
    public function parentTaskName(): ?string { return $this->parentTaskName; }
    public function totalTimeSpent(): int { return $this->totalTimeSpent; }
    public function status(): string { return $this->status; }
    public function comments(): array { return $this->comments; }
    public function worklogs(): array { return $this->worklogs; }
    public function subtasks(): array { return $this->subtasks; }
    public function createdAt(): string { return $this->createdAt; }
    public function updatedAt(): string { return $this->updatedAt; }
}

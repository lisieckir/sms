<?php

declare(strict_types=1);

namespace App\TaskManagement\Domain\Model;

use Symfony\Component\Uid\Uuid;

final readonly class Worklog
{
    public function __construct(
        private string $id,
        private string $userId,
        private int $minutes,
        private string $description,
        private \DateTimeImmutable $date,
        private \DateTimeImmutable $createdAt,
    ) {
        if ($minutes < 1) {
            throw new \InvalidArgumentException('Worklog minutes must be positive');
        }
    }

    public static function create(string $userId, int $minutes, string $description, ?\DateTimeImmutable $date = null): self
    {
        return new self(
            Uuid::v4()->toRfc4122(),
            $userId,
            $minutes,
            $description,
            $date ?? new \DateTimeImmutable(),
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

    public function minutes(): int
    {
        return $this->minutes;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function date(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}

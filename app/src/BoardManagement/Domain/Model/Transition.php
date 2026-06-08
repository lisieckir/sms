<?php

declare(strict_types=1);

namespace App\BoardManagement\Domain\Model;

use Symfony\Component\Uid\Uuid;

final readonly class Transition
{
    public function __construct(
        private string $id,
        private string $fromStageId,
        private string $toStageId,
    ) {
        if ($fromStageId === $toStageId) {
            throw new \InvalidArgumentException('Transition must be between different stages');
        }
    }

    public static function create(string $fromStageId, string $toStageId): self
    {
        return new self(Uuid::v4()->toRfc4122(), $fromStageId, $toStageId);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function fromStageId(): string
    {
        return $this->fromStageId;
    }

    public function toStageId(): string
    {
        return $this->toStageId;
    }

    public function matches(string $from, string $to): bool
    {
        return $this->fromStageId === $from && $this->toStageId === $to;
    }
}

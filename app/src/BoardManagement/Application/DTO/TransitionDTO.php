<?php

declare(strict_types=1);

namespace App\BoardManagement\Application\DTO;

final class TransitionDTO
{
    public function __construct(
        private string $id,
        private string $fromStageId,
        private string $toStageId,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['fromStageId'],
            $data['toStageId'],
        );
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

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'fromStageId' => $this->fromStageId,
            'toStageId' => $this->toStageId,
        ];
    }
}

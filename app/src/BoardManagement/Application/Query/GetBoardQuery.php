<?php

declare(strict_types=1);

namespace App\BoardManagement\Application\Query;

final class GetBoardQuery
{
    public function __construct(
        private ?string $clientId = null,
        private ?string $assigneeId = null,
    ) {}

    public function clientId(): ?string
    {
        return $this->clientId;
    }

    public function assigneeId(): ?string
    {
        return $this->assigneeId;
    }
}

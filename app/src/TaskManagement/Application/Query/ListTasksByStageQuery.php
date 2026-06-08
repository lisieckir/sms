<?php

declare(strict_types=1);

namespace App\TaskManagement\Application\Query;

final class ListTasksByStageQuery
{
    public function __construct(
        private string $stageId,
    ) {}

    public function stageId(): string { return $this->stageId; }
}

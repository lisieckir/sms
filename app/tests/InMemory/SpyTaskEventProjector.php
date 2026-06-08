<?php

declare(strict_types=1);

namespace App\Tests\InMemory;

use App\TaskManagement\Infrastructure\Projection\TaskEventProjector;

class SpyTaskEventProjector extends TaskEventProjector
{
    public array $projectedEvents = [];

    public function __construct()
    {
    }

    public function project(object $event): void
    {
        $this->projectedEvents[] = $event;
    }
}

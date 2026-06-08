<?php

declare(strict_types=1);

namespace App\ClientManagement\Application\Command\BlockClient;

final class BlockClientCommand
{
    public function __construct(
        private string $clientId,
        private bool $block,
    ) {}

    public function clientId(): string { return $this->clientId; }
    public function block(): bool { return $this->block; }
}

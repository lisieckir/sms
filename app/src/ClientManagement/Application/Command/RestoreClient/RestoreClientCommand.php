<?php

declare(strict_types=1);

namespace App\ClientManagement\Application\Command\RestoreClient;

final class RestoreClientCommand
{
    public function __construct(
        private string $clientId,
    ) {}

    public function clientId(): string { return $this->clientId; }
}

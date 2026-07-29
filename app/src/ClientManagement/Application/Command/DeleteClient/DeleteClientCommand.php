<?php

declare(strict_types=1);

namespace App\ClientManagement\Application\Command\DeleteClient;

final class DeleteClientCommand
{
    public function __construct(
        private string $clientId,
    ) {}

    public function clientId(): string { return $this->clientId; }
}

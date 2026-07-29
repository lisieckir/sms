<?php

declare(strict_types=1);

namespace App\ClientManagement\Application\Command\UpdateClient;

final class UpdateClientCommand
{
    public function __construct(
        private string $clientId,
        private string $name,
        private string $address,
        private string $country,
        private ?string $email = null,
        private string $description = '',
        private ?string $settlementType = null,
    ) {}

    public function clientId(): string { return $this->clientId; }
    public function name(): string { return $this->name; }
    public function address(): string { return $this->address; }
    public function country(): string { return $this->country; }
    public function email(): ?string { return $this->email; }
    public function description(): string { return $this->description; }
    public function settlementType(): ?string { return $this->settlementType; }
}

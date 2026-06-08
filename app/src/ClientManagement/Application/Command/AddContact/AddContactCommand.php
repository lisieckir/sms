<?php

declare(strict_types=1);

namespace App\ClientManagement\Application\Command\AddContact;

final class AddContactCommand
{
    public function __construct(
        private string $clientId,
        private string $firstName,
        private string $lastName,
        private string $email,
        private ?string $phone = null,
    ) {}

    public function clientId(): string { return $this->clientId; }
    public function firstName(): string { return $this->firstName; }
    public function lastName(): string { return $this->lastName; }
    public function email(): string { return $this->email; }
    public function phone(): ?string { return $this->phone; }
}

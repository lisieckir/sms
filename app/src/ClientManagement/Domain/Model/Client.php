<?php

declare(strict_types=1);

namespace App\ClientManagement\Domain\Model;

use App\ClientManagement\Domain\Event\ClientRegistered;
use App\ClientManagement\Domain\Event\Trait\EventRecordingCapabilities;

class Client
{
    use EventRecordingCapabilities;

    private \DateTimeImmutable $updatedAt;
    private array $contacts = [];

    private function __construct(
        private ClientId $id,
        private ClientNip $nip,
        private string $name,
        private string $address,
        private string $country,
        private string $email,
        private string $description,
        private string $status,
        private \DateTimeImmutable $createdAt,
    ) {
        $this->updatedAt = $createdAt;
    }

    public static function register(
        ClientId $id,
        ClientNip $nip,
        string $name,
        string $address,
        string $country,
        string $email,
        string $description = '',
    ): self {
        $client = new self(
            $id,
            $nip,
            $name,
            $address,
            $country,
            $email,
            $description,
            'active',
            new \DateTimeImmutable(),
        );
        $client->recordEvent(new ClientRegistered($id, $name, new \DateTimeImmutable()));
        return $client;
    }

    public function update(
        string $name,
        string $address,
        string $country,
        string $email,
        string $description,
    ): void {
        $this->name = $name;
        $this->address = $address;
        $this->country = $country;
        $this->email = $email;
        $this->description = $description;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function block(): void
    {
        $this->status = 'blocked';
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function unblock(): void
    {
        $this->status = 'active';
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function addContact(string $firstName, string $lastName, string $email, ?string $phone = null): void
    {
        $this->contacts[] = Contact::create($firstName, $lastName, $email, $phone);
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function id(): ClientId { return $this->id; }
    public function nip(): ClientNip { return $this->nip; }
    public function name(): string { return $this->name; }
    public function address(): string { return $this->address; }
    public function country(): string { return $this->country; }
    public function email(): string { return $this->email; }
    public function description(): string { return $this->description; }
    public function contacts(): array { return $this->contacts; }
    public function status(): string { return $this->status; }
    public function createdAt(): \DateTimeImmutable { return $this->createdAt; }
    public function updatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function isActive(): bool { return $this->status === 'active'; }
    public function isBlocked(): bool { return $this->status === 'blocked'; }
}

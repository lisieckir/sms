<?php

declare(strict_types=1);

namespace App\ClientManagement\Domain\Model;

use Symfony\Component\Uid\Uuid;

final readonly class Contact
{
    public function __construct(
        private string $id,
        private string $firstName,
        private string $lastName,
        private string $email,
        private ?string $phone,
    ) {}

    public static function create(string $firstName, string $lastName, string $email, ?string $phone = null): self
    {
        return new self(Uuid::v4()->toRfc4122(), $firstName, $lastName, $email, $phone);
    }

    public function id(): string { return $this->id; }
    public function firstName(): string { return $this->firstName; }
    public function lastName(): string { return $this->lastName; }
    public function email(): string { return $this->email; }
    public function phone(): ?string { return $this->phone; }
}

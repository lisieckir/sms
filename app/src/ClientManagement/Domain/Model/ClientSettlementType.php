<?php

declare(strict_types=1);

namespace App\ClientManagement\Domain\Model;

final readonly class ClientSettlementType
{
    public const USE = 'useme';
    public const B2B = 'b2b';
    public const CONTRACT = 'umowa_zlecenie';

    private const VALID = [self::USE, self::B2B, self::CONTRACT];

    public function __construct(private string $value)
    {
        if (!in_array($value, self::VALID, true)) {
            throw new \InvalidArgumentException("Invalid settlement type: $value. Allowed: " . implode(', ', self::VALID));
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

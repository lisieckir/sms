<?php

declare(strict_types=1);

namespace App\IdentityAccess\Domain\Exception;

class UserNotFound extends \RuntimeException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('User with ID "%s" not found', $id));
    }

    public static function withEmail(string $email): self
    {
        return new self(sprintf('User with email "%s" not found', $email));
    }

    public static function withUsername(string $username): self
    {
        return new self(sprintf('User with username "%s" not found', $username));
    }
}

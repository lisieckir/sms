<?php

declare(strict_types=1);

namespace App\IdentityAccess\Domain\Exception;

class EmailAlreadyInUse extends \RuntimeException
{
    public function __construct(string $email)
    {
        parent::__construct(sprintf('Email "%s" is already in use', $email));
    }
}

<?php

declare(strict_types=1);

namespace App\IdentityAccess\Application\Query;

use App\IdentityAccess\Domain\Model\UserRepositoryInterface;

final readonly class ListUsersHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(ListUsersQuery $query): array
    {
        return $this->userRepository->findAll();
    }
}

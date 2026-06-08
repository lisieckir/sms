<?php

declare(strict_types=1);

namespace App\ClientManagement\Application\Query;

use App\ClientManagement\Domain\Model\Client;
use App\ClientManagement\Domain\Model\ClientId;
use App\ClientManagement\Domain\Model\ClientRepositoryInterface;

final class GetClientHandler
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
    ) {}

    public function __invoke(GetClientQuery $query): ?Client
    {
        return $this->clientRepository->findById(new ClientId($query->clientId()));
    }
}

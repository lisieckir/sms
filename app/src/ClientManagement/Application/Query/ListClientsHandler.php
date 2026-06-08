<?php

declare(strict_types=1);

namespace App\ClientManagement\Application\Query;

use App\ClientManagement\Domain\Model\Client;
use App\ClientManagement\Domain\Model\ClientRepositoryInterface;

final class ListClientsHandler
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
    ) {}

    /** @return Client[] */
    public function __invoke(ListClientsQuery $query): array
    {
        return $this->clientRepository->findAll();
    }
}

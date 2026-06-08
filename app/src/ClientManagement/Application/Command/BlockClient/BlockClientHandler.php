<?php

declare(strict_types=1);

namespace App\ClientManagement\Application\Command\BlockClient;

use App\ClientManagement\Domain\Model\ClientId;
use App\ClientManagement\Domain\Model\ClientRepositoryInterface;

final class BlockClientHandler
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
    ) {}

    public function __invoke(BlockClientCommand $command): void
    {
        $client = $this->clientRepository->findById(new ClientId($command->clientId()));
        if ($client === null) {
            throw new \InvalidArgumentException('Client not found');
        }

        $command->block() ? $client->block() : $client->unblock();
        $this->clientRepository->save($client);
    }
}

<?php

declare(strict_types=1);

namespace App\ClientManagement\Application\Command\DeleteClient;

use App\ClientManagement\Domain\Model\ClientId;
use App\ClientManagement\Domain\Model\ClientRepositoryInterface;

final class DeleteClientHandler
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
    ) {}

    public function __invoke(DeleteClientCommand $command): void
    {
        $client = $this->clientRepository->findById(new ClientId($command->clientId()));
        if ($client === null) {
            throw new \InvalidArgumentException('Client not found');
        }

        $client->delete();
        $this->clientRepository->save($client);
    }
}

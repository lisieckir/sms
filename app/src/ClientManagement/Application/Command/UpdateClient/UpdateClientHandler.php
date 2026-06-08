<?php

declare(strict_types=1);

namespace App\ClientManagement\Application\Command\UpdateClient;

use App\ClientManagement\Domain\Model\ClientId;
use App\ClientManagement\Domain\Model\ClientRepositoryInterface;

final class UpdateClientHandler
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
    ) {}

    public function __invoke(UpdateClientCommand $command): void
    {
        $client = $this->clientRepository->findById(new ClientId($command->clientId()));
        if ($client === null) {
            throw new \InvalidArgumentException('Client not found');
        }

        $client->update(
            name: $command->name(),
            address: $command->address(),
            country: $command->country(),
            email: $command->email(),
            description: $command->description(),
        );
        $this->clientRepository->save($client);
    }
}

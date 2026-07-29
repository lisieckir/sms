<?php

declare(strict_types=1);

namespace App\ClientManagement\Application\Command\RegisterClient;

use App\ClientManagement\Domain\Model\Client;
use App\ClientManagement\Domain\Model\ClientNip;
use App\ClientManagement\Domain\Model\ClientRepositoryInterface;
use App\ClientManagement\Domain\Model\ClientSettlementType;

final class RegisterClientHandler
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
    ) {}

    public function __invoke(RegisterClientCommand $command): string
    {
        $id = $this->clientRepository->nextIdentity();
        $client = Client::register(
            id: $id,
            nip: new ClientNip($command->nip()),
            name: $command->name(),
            address: $command->address(),
            country: $command->country(),
            email: $command->email(),
            description: $command->description(),
            settlementType: $command->settlementType() !== null ? new ClientSettlementType($command->settlementType()) : null,
        );
        $this->clientRepository->save($client);
        return $id->value();
    }
}

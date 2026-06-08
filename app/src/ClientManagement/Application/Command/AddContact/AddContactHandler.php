<?php

declare(strict_types=1);

namespace App\ClientManagement\Application\Command\AddContact;

use App\ClientManagement\Domain\Model\ClientId;
use App\ClientManagement\Domain\Model\ClientRepositoryInterface;

final class AddContactHandler
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
    ) {}

    public function __invoke(AddContactCommand $command): void
    {
        $client = $this->clientRepository->findById(new ClientId($command->clientId()));
        if ($client === null) {
            throw new \InvalidArgumentException('Client not found');
        }

        $client->addContact(
            firstName: $command->firstName(),
            lastName: $command->lastName(),
            email: $command->email(),
            phone: $command->phone(),
        );
        $this->clientRepository->save($client);
    }
}

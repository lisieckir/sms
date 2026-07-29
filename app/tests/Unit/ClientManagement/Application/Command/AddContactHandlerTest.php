<?php

declare(strict_types=1);

namespace App\Tests\Unit\ClientManagement\Application\Command;

use App\ClientManagement\Application\Command\AddContact\AddContactCommand;
use App\ClientManagement\Application\Command\AddContact\AddContactHandler;
use App\ClientManagement\Domain\Model\Client;
use App\ClientManagement\Domain\Model\ClientId;
use App\ClientManagement\Domain\Model\ClientNip;
use App\Tests\InMemory\InMemoryClientRepository;
use PHPUnit\Framework\TestCase;

class AddContactHandlerTest extends TestCase
{
    private InMemoryClientRepository $clientRepository;
    private AddContactHandler $handler;

    protected function setUp(): void
    {
        $this->clientRepository = new InMemoryClientRepository();
        $this->handler = new AddContactHandler(
            $this->clientRepository,
        );
    }

    public function testAddContact(): void
    {
        $client = $this->createClient();
        $clientId = $client->id()->value();

        $this->handler->__invoke(new AddContactCommand(
            clientId: $clientId,
            firstName: 'Jane',
            lastName: 'Doe',
            email: 'jane@example.com',
            phone: '555-0100',
        ));

        $updated = $this->clientRepository->findById(new ClientId($clientId));
        $this->assertCount(1, $updated->contacts());
        $this->assertSame('Jane', $updated->contacts()[0]->firstName());
        $this->assertSame('555-0100', $updated->contacts()[0]->phone());
    }

    public function testAddContactWithoutPhone(): void
    {
        $client = $this->createClient();
        $clientId = $client->id()->value();

        $this->handler->__invoke(new AddContactCommand(
            clientId: $clientId,
            firstName: 'Bob',
            lastName: 'Smith',
            email: 'bob@example.com',
        ));

        $updated = $this->clientRepository->findById(new ClientId($clientId));
        $this->assertCount(1, $updated->contacts());
        $this->assertNull($updated->contacts()[0]->phone());
    }

    public function testAddContactClientNotFoundThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->handler->__invoke(new AddContactCommand(
            clientId: '00000000-0000-0000-0000-000000000000',
            firstName: 'Jane',
            lastName: 'Doe',
            email: 'jane@example.com',
        ));
    }

    private function createClient(): Client
    {
        $id = $this->clientRepository->nextIdentity();
        $client = Client::register(
            $id,
            new ClientNip('1234567890'),
            'Acme Corp',
            '123 Main St',
            'US',
            'acme@example.com',
        );
        $this->clientRepository->save($client);
        return $client;
    }
}

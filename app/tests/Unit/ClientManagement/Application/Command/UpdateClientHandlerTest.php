<?php

declare(strict_types=1);

namespace App\Tests\Unit\ClientManagement\Application\Command;

use App\ClientManagement\Application\Command\UpdateClient\UpdateClientCommand;
use App\ClientManagement\Application\Command\UpdateClient\UpdateClientHandler;
use App\ClientManagement\Domain\Model\Client;
use App\ClientManagement\Domain\Model\ClientId;
use App\ClientManagement\Domain\Model\ClientNip;
use App\Tests\InMemory\InMemoryClientRepository;
use PHPUnit\Framework\TestCase;

class UpdateClientHandlerTest extends TestCase
{
    private InMemoryClientRepository $clientRepository;
    private UpdateClientHandler $handler;

    protected function setUp(): void
    {
        $this->clientRepository = new InMemoryClientRepository();
        $this->handler = new UpdateClientHandler(
            $this->clientRepository,
        );
    }

    public function testUpdateClient(): void
    {
        $client = $this->createClient();
        $clientId = $client->id()->value();

        $this->handler->__invoke(new UpdateClientCommand(
            clientId: $clientId,
            name: 'New Name',
            address: '456 New St',
            country: 'CA',
            email: 'new@example.com',
            description: 'Updated description',
        ));

        $updated = $this->clientRepository->findById(new ClientId($clientId));
        $this->assertSame('New Name', $updated->name());
        $this->assertSame('456 New St', $updated->address());
        $this->assertSame('CA', $updated->country());
        $this->assertSame('new@example.com', $updated->email());
    }

    public function testUpdateClientNotFoundThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->handler->__invoke(new UpdateClientCommand(
            clientId: '00000000-0000-0000-0000-000000000000',
            name: 'N/A',
            address: 'N/A',
            country: 'N/A',
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

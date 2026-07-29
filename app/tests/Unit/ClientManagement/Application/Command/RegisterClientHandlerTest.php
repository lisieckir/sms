<?php

declare(strict_types=1);

namespace App\Tests\Unit\ClientManagement\Application\Command;

use App\ClientManagement\Application\Command\RegisterClient\RegisterClientCommand;
use App\ClientManagement\Application\Command\RegisterClient\RegisterClientHandler;
use App\ClientManagement\Domain\Model\ClientId;
use App\Tests\InMemory\InMemoryClientRepository;
use PHPUnit\Framework\TestCase;

class RegisterClientHandlerTest extends TestCase
{
    private InMemoryClientRepository $clientRepository;
    private RegisterClientHandler $handler;

    protected function setUp(): void
    {
        $this->clientRepository = new InMemoryClientRepository();
        $this->handler = new RegisterClientHandler(
            $this->clientRepository,
        );
    }

    public function testRegisterClient(): void
    {
        $clientId = $this->handler->__invoke(new RegisterClientCommand(
            nip: '1234567890',
            name: 'Acme Corp',
            address: '123 Main St',
            country: 'US',
            email: 'acme@example.com',
            description: 'A test client',
        ));

        $this->assertNotEmpty($clientId);

        $client = $this->clientRepository->findById(new ClientId($clientId));
        $this->assertNotNull($client);
        $this->assertSame('Acme Corp', $client->name());
        $this->assertSame('123 Main St', $client->address());
        $this->assertSame('US', $client->country());
        $this->assertSame('acme@example.com', $client->email());
        $this->assertTrue($client->isActive());
    }

    public function testRegisterClientWithoutEmail(): void
    {
        $clientId = $this->handler->__invoke(new RegisterClientCommand(
            nip: '0987654321',
            name: 'No Email Inc',
            address: '456 Oak Ave',
            country: 'PL',
        ));

        $client = $this->clientRepository->findById(new ClientId($clientId));
        $this->assertNotNull($client);
        $this->assertNull($client->email());
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\ClientManagement\Application\Query;

use App\ClientManagement\Application\Query\GetClientHandler;
use App\ClientManagement\Application\Query\GetClientQuery;
use App\ClientManagement\Domain\Model\Client;
use App\ClientManagement\Domain\Model\ClientId;
use App\ClientManagement\Domain\Model\ClientNip;
use App\Tests\InMemory\InMemoryClientRepository;
use PHPUnit\Framework\TestCase;

class GetClientHandlerTest extends TestCase
{
    private InMemoryClientRepository $clientRepository;
    private GetClientHandler $handler;

    protected function setUp(): void
    {
        $this->clientRepository = new InMemoryClientRepository();
        $this->handler = new GetClientHandler(
            $this->clientRepository,
        );
    }

    public function testGetClientReturnsClient(): void
    {
        $id = ClientId::generate();
        $client = Client::register(
            $id,
            new ClientNip('1234567890'),
            'Acme Corp',
            '123 Main St',
            'US',
            'acme@example.com',
            'A test client',
        );
        $client->releaseEvents();
        $this->clientRepository->save($client);

        $result = $this->handler->__invoke(new GetClientQuery($id->value()));

        $this->assertInstanceOf(Client::class, $result);
        $this->assertSame('Acme Corp', $result->name());
        $this->assertSame('acme@example.com', $result->email());
        $this->assertTrue($result->isActive());
    }

    public function testGetClientReturnsNullWhenNotFound(): void
    {
        $result = $this->handler->__invoke(new GetClientQuery('00000000-0000-0000-0000-000000000000'));
        $this->assertNull($result);
    }

    public function testGetClientReturnsDeletedClient(): void
    {
        $id = ClientId::generate();
        $client = Client::register(
            $id,
            new ClientNip('1234567890'),
            'Deleted Co',
            '456 Oak Ave',
            'PL',
        );
        $client->delete();
        $client->releaseEvents();
        $this->clientRepository->save($client);

        $result = $this->handler->__invoke(new GetClientQuery($id->value()));

        $this->assertNotNull($result);
        $this->assertTrue($result->isDeleted());
    }
}

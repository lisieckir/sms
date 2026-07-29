<?php

declare(strict_types=1);

namespace App\Tests\Unit\ClientManagement\Application\Query;

use App\ClientManagement\Application\Query\ListClientsHandler;
use App\ClientManagement\Application\Query\ListClientsQuery;
use App\ClientManagement\Domain\Model\Client;
use App\ClientManagement\Domain\Model\ClientId;
use App\ClientManagement\Domain\Model\ClientNip;
use App\Tests\InMemory\InMemoryClientRepository;
use PHPUnit\Framework\TestCase;

class ListClientsHandlerTest extends TestCase
{
    private InMemoryClientRepository $clientRepository;
    private ListClientsHandler $handler;

    protected function setUp(): void
    {
        $this->clientRepository = new InMemoryClientRepository();
        $this->handler = new ListClientsHandler(
            $this->clientRepository,
        );
    }

    public function testListClientsReturnsAllActiveClients(): void
    {
        $this->createClient('Acme Corp');
        $this->createClient('Globex Inc');

        $clients = $this->handler->__invoke(new ListClientsQuery());

        $this->assertCount(2, $clients);
        $this->assertContainsOnlyInstancesOf(Client::class, $clients);
    }

    public function testListClientsExcludesDeletedClients(): void
    {
        $active = $this->createClient('Active Co');
        $deleted = $this->createClient('Deleted Co');
        $deleted->delete();
        $deleted->releaseEvents();
        $this->clientRepository->save($deleted);

        $clients = $this->handler->__invoke(new ListClientsQuery());

        $this->assertCount(1, $clients);
        $this->assertSame('Active Co', $clients[0]->name());
    }

    public function testListClientsEmptyWhenNoClients(): void
    {
        $clients = $this->handler->__invoke(new ListClientsQuery());
        $this->assertEmpty($clients);
    }

    private function createClient(string $name): Client
    {
        $client = Client::register(
            ClientId::generate(),
            new ClientNip('1234567890'),
            $name,
            '123 Main St',
            'US',
        );
        $client->releaseEvents();
        $this->clientRepository->save($client);
        return $client;
    }
}

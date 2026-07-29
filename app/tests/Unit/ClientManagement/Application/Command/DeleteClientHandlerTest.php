<?php

declare(strict_types=1);

namespace App\Tests\Unit\ClientManagement\Application\Command;

use App\ClientManagement\Application\Command\DeleteClient\DeleteClientCommand;
use App\ClientManagement\Application\Command\DeleteClient\DeleteClientHandler;
use App\ClientManagement\Domain\Model\Client;
use App\ClientManagement\Domain\Model\ClientId;
use App\ClientManagement\Domain\Model\ClientNip;
use App\Tests\InMemory\InMemoryClientRepository;
use PHPUnit\Framework\TestCase;

class DeleteClientHandlerTest extends TestCase
{
    private InMemoryClientRepository $clientRepository;
    private DeleteClientHandler $handler;

    protected function setUp(): void
    {
        $this->clientRepository = new InMemoryClientRepository();
        $this->handler = new DeleteClientHandler(
            $this->clientRepository,
        );
    }

    public function testDeleteClient(): void
    {
        $client = $this->createClient();
        $clientId = $client->id()->value();

        $this->handler->__invoke(new DeleteClientCommand(clientId: $clientId));

        $updated = $this->clientRepository->findById(new ClientId($clientId));
        $this->assertTrue($updated->isDeleted());
        $this->assertNotNull($updated->deletedAt());
    }

    public function testDeleteClientExcludesFromFindAll(): void
    {
        $client = $this->createClient();
        $this->handler->__invoke(new DeleteClientCommand(clientId: $client->id()->value()));

        $all = $this->clientRepository->findAll();
        $this->assertCount(0, $all);
    }

    public function testDeleteClientNotFoundThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->handler->__invoke(new DeleteClientCommand(
            clientId: '00000000-0000-0000-0000-000000000000',
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

<?php

declare(strict_types=1);

namespace App\Tests\Unit\ClientManagement\Application\Command;

use App\ClientManagement\Application\Command\RestoreClient\RestoreClientCommand;
use App\ClientManagement\Application\Command\RestoreClient\RestoreClientHandler;
use App\ClientManagement\Domain\Model\Client;
use App\ClientManagement\Domain\Model\ClientId;
use App\ClientManagement\Domain\Model\ClientNip;
use App\Tests\InMemory\InMemoryClientRepository;
use PHPUnit\Framework\TestCase;

class RestoreClientHandlerTest extends TestCase
{
    private InMemoryClientRepository $clientRepository;
    private RestoreClientHandler $handler;

    protected function setUp(): void
    {
        $this->clientRepository = new InMemoryClientRepository();
        $this->handler = new RestoreClientHandler(
            $this->clientRepository,
        );
    }

    public function testRestoreClient(): void
    {
        $client = $this->createClient();
        $client->delete();
        $this->clientRepository->save($client);
        $clientId = $client->id()->value();

        $this->handler->__invoke(new RestoreClientCommand(clientId: $clientId));

        $updated = $this->clientRepository->findById(new ClientId($clientId));
        $this->assertTrue($updated->isActive());
        $this->assertNull($updated->deletedAt());
    }

    public function testRestoreClientAppearsInFindAll(): void
    {
        $client = $this->createClient();
        $client->delete();
        $this->clientRepository->save($client);

        $this->handler->__invoke(new RestoreClientCommand(clientId: $client->id()->value()));

        $all = $this->clientRepository->findAll();
        $this->assertCount(1, $all);
    }

    public function testRestoreClientNotFoundThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->handler->__invoke(new RestoreClientCommand(
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

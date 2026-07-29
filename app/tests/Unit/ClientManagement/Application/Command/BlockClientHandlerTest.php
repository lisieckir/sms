<?php

declare(strict_types=1);

namespace App\Tests\Unit\ClientManagement\Application\Command;

use App\ClientManagement\Application\Command\BlockClient\BlockClientCommand;
use App\ClientManagement\Application\Command\BlockClient\BlockClientHandler;
use App\ClientManagement\Domain\Model\Client;
use App\ClientManagement\Domain\Model\ClientId;
use App\ClientManagement\Domain\Model\ClientNip;
use App\Tests\InMemory\InMemoryClientRepository;
use PHPUnit\Framework\TestCase;

class BlockClientHandlerTest extends TestCase
{
    private InMemoryClientRepository $clientRepository;
    private BlockClientHandler $handler;

    protected function setUp(): void
    {
        $this->clientRepository = new InMemoryClientRepository();
        $this->handler = new BlockClientHandler(
            $this->clientRepository,
        );
    }

    public function testBlockClient(): void
    {
        $client = $this->createClient();
        $clientId = $client->id()->value();

        $this->handler->__invoke(new BlockClientCommand(
            clientId: $clientId,
            block: true,
        ));

        $updated = $this->clientRepository->findById(new ClientId($clientId));
        $this->assertTrue($updated->isBlocked());
        $this->assertFalse($updated->isActive());
    }

    public function testUnblockClient(): void
    {
        $client = $this->createClient();
        $clientId = $client->id()->value();
        $client->block();
        $this->clientRepository->save($client);

        $this->handler->__invoke(new BlockClientCommand(
            clientId: $clientId,
            block: false,
        ));

        $updated = $this->clientRepository->findById(new ClientId($clientId));
        $this->assertTrue($updated->isActive());
        $this->assertFalse($updated->isBlocked());
    }

    public function testBlockClientNotFoundThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->handler->__invoke(new BlockClientCommand(
            clientId: '00000000-0000-0000-0000-000000000000',
            block: true,
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

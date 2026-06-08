<?php

declare(strict_types=1);

namespace App\Tests\Unit\ClientManagement\Domain\Model;

use App\ClientManagement\Domain\Model\Client;
use App\ClientManagement\Domain\Model\ClientId;
use App\ClientManagement\Domain\Model\ClientNip;
use App\ClientManagement\Domain\Event\ClientRegistered;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    public function testRegister(): void
    {
        $id = ClientId::generate();
        $nip = new ClientNip('1234567890');

        $client = Client::register(
            $id,
            $nip,
            'Acme Corp',
            '123 Main St',
            'US',
            'acme@example.com',
            'A client',
        );

        $this->assertTrue($id->equals($client->id()));
        $this->assertSame('Acme Corp', $client->name());
        $this->assertSame('123 Main St', $client->address());
        $this->assertSame('US', $client->country());
        $this->assertSame('acme@example.com', $client->email());
        $this->assertSame('A client', $client->description());
        $this->assertTrue($client->isActive());
        $this->assertFalse($client->isBlocked());

        $events = $client->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(ClientRegistered::class, $events[0]);
    }

    public function testUpdate(): void
    {
        $client = $this->createClient();
        $client->releaseEvents();

        $client->update('New Name', '456 Oak St', 'CA', 'new@example.com', 'Updated');

        $this->assertSame('New Name', $client->name());
        $this->assertSame('456 Oak St', $client->address());
        $this->assertSame('CA', $client->country());
        $this->assertSame('new@example.com', $client->email());
        $this->assertSame('Updated', $client->description());
    }

    public function testBlock(): void
    {
        $client = $this->createClient();

        $client->block();

        $this->assertTrue($client->isBlocked());
        $this->assertFalse($client->isActive());
    }

    public function testUnblock(): void
    {
        $client = $this->createClient();
        $client->block();

        $client->unblock();

        $this->assertTrue($client->isActive());
        $this->assertFalse($client->isBlocked());
    }

    public function testAddContact(): void
    {
        $client = $this->createClient();

        $client->addContact('Jane', 'Doe', 'jane@example.com', '555-0100');

        $this->assertCount(1, $client->contacts());
        $contact = $client->contacts()[0];
        $this->assertSame('Jane', $contact->firstName());
        $this->assertSame('Doe', $contact->lastName());
        $this->assertSame('jane@example.com', $contact->email());
        $this->assertSame('555-0100', $contact->phone());
    }

    public function testAddContactWithoutPhone(): void
    {
        $client = $this->createClient();

        $client->addContact('Bob', 'Smith', 'bob@example.com');

        $this->assertCount(1, $client->contacts());
        $this->assertNull($client->contacts()[0]->phone());
    }

    private function createClient(): Client
    {
        return Client::register(
            ClientId::generate(),
            new ClientNip('1234567890'),
            'Acme Corp',
            '123 Main St',
            'US',
            'acme@example.com',
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\InMemory;

use App\ClientManagement\Domain\Model\Client;
use App\ClientManagement\Domain\Model\ClientId;
use App\ClientManagement\Domain\Model\ClientNip;
use App\ClientManagement\Domain\Model\ClientRepositoryInterface;

class InMemoryClientRepository implements ClientRepositoryInterface
{
    /** @var array<string, Client> */
    private array $clients = [];

    public function save(Client $client): void
    {
        $this->clients[$client->id()->value()] = $client;
    }

    public function findById(ClientId $id): ?Client
    {
        return $this->clients[$id->value()] ?? null;
    }

    public function findByNip(ClientNip $nip): ?Client
    {
        foreach ($this->clients as $client) {
            if ($client->nip()->value() === $nip->value()) {
                return $client;
            }
        }
        return null;
    }

    public function findAll(): array
    {
        return array_values(array_filter($this->clients, fn(Client $c) => !$c->isDeleted()));
    }

    public function searchByTerm(string $term): array
    {
        return array_values(array_filter($this->clients, fn(Client $c) =>
            !$c->isDeleted()
            && (stripos($c->name(), $term) !== false
                || ($c->email() !== null && stripos($c->email(), $term) !== false))
        ));
    }

    public function nextIdentity(): ClientId
    {
        return ClientId::generate();
    }
}

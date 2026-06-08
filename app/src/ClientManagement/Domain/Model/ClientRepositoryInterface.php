<?php

declare(strict_types=1);

namespace App\ClientManagement\Domain\Model;

interface ClientRepositoryInterface
{
    public function save(Client $client): void;
    public function findById(ClientId $id): ?Client;
    public function findByNip(ClientNip $nip): ?Client;
    public function findAll(): array;
    public function searchByTerm(string $term): array;
    public function nextIdentity(): ClientId;
}

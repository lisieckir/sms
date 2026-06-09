<?php

declare(strict_types=1);

namespace App\ClientManagement\Infrastructure\PocketBase;

use App\ClientManagement\Domain\Model\Client;
use App\ClientManagement\Domain\Model\ClientId;
use App\ClientManagement\Domain\Model\ClientNip;
use App\ClientManagement\Domain\Model\ClientRepositoryInterface;
use App\ClientManagement\Domain\Model\Contact;
use App\Core\Infrastructure\PocketBase\PocketBaseClient;

final readonly class ClientRepository implements ClientRepositoryInterface
{
    private const COLLECTION = 'clients';

    public function __construct(
        private PocketBaseClient $pb,
    ) {}

    public function save(Client $client): void
    {
        $data = [
            'clientId' => $client->id()->value(),
            'nip' => $client->nip()->value(),
            'name' => $client->name(),
            'address' => $client->address(),
            'country' => $client->country(),
            'email' => $client->email(),
            'description' => $client->description(),
            'status' => $client->status(),
            'contacts' => array_map(fn(Contact $c) => [
                'id' => $c->id(),
                'firstName' => $c->firstName(),
                'lastName' => $c->lastName(),
                'email' => $c->email(),
                'phone' => $c->phone(),
            ], $client->contacts()),
            'created' => $client->createdAt()->format('c'),
            'updated' => $client->updatedAt()->format('c'),
        ];

        $this->pb->upsert(self::COLLECTION, sprintf('clientId="%s"', $client->id()->value()), $data);
    }

    public function findById(ClientId $id): ?Client
    {
        $record = $this->pb->first(self::COLLECTION, sprintf('clientId="%s"', $id->value()));
        return $record !== null ? $this->toDomain($record) : null;
    }

    public function findByNip(ClientNip $nip): ?Client
    {
        $record = $this->pb->first(self::COLLECTION, sprintf('nip="%s"', $nip->value()));
        return $record !== null ? $this->toDomain($record) : null;
    }

    public function findAll(): array
    {
        $result = $this->pb->list(self::COLLECTION);
        return array_map(fn(array $r) => $this->toDomain($r), $result['items']);
    }

    public function searchByTerm(string $term): array
    {
        $escaped = addslashes($term);
        $filter = sprintf('name~"%s" || email~"%s" || nip~"%s"', $escaped, $escaped, $escaped);
        $result = $this->pb->list(self::COLLECTION, ['filter' => $filter]);
        return array_map(fn(array $r) => $this->toDomain($r), $result['items']);
    }

    public function nextIdentity(): ClientId
    {
        return ClientId::generate();
    }

    private function toDomain(array $record): Client
    {
        $fields = $record;

        $client = new \ReflectionClass(Client::class);
        $instance = $client->newInstanceWithoutConstructor();

        $idProp = $client->getProperty('id');
        $idProp->setAccessible(true);
        $idProp->setValue($instance, new ClientId($fields['clientId']));

        $nipProp = $client->getProperty('nip');
        $nipProp->setAccessible(true);
        $nipProp->setValue($instance, new ClientNip($fields['nip']));

        $nameProp = $client->getProperty('name');
        $nameProp->setAccessible(true);
        $nameProp->setValue($instance, $fields['name']);

        $addrProp = $client->getProperty('address');
        $addrProp->setAccessible(true);
        $addrProp->setValue($instance, $fields['address']);

        $countryProp = $client->getProperty('country');
        $countryProp->setAccessible(true);
        $countryProp->setValue($instance, $fields['country']);

        $emailProp = $client->getProperty('email');
        $emailProp->setAccessible(true);
        $emailProp->setValue($instance, $fields['email']);

        $descProp = $client->getProperty('description');
        $descProp->setAccessible(true);
        $descProp->setValue($instance, $fields['description']);

        $statusProp = $client->getProperty('status');
        $statusProp->setAccessible(true);
        $statusProp->setValue($instance, $fields['status']);

        $contactsProp = $client->getProperty('contacts');
        $contactsProp->setAccessible(true);
        $contactsProp->setValue($instance, array_map(
            fn(array $c) => Contact::create(
                $c['firstName'],
                $c['lastName'],
                $c['email'],
                $c['phone'] ?? null,
            ),
            $fields['contacts'] ?? [],
        ));

        $createdProp = $client->getProperty('createdAt');
        $createdProp->setAccessible(true);
        $createdProp->setValue($instance, new \DateTimeImmutable($fields['created']));

        $updatedProp = $client->getProperty('updatedAt');
        $updatedProp->setAccessible(true);
        $updatedProp->setValue($instance, new \DateTimeImmutable($fields['updated']));

        return $instance;
    }
}

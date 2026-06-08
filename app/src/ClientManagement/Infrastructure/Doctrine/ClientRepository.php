<?php

declare(strict_types=1);

namespace App\ClientManagement\Infrastructure\Doctrine;

use App\ClientManagement\Domain\Model\Client;
use App\ClientManagement\Domain\Model\ClientId;
use App\ClientManagement\Domain\Model\ClientNip;
use App\ClientManagement\Domain\Model\ClientRepositoryInterface;
use App\ClientManagement\Domain\Model\Contact;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

class ClientRepository implements ClientRepositoryInterface
{
    private DocumentRepository $repository;

    public function __construct(
        private DocumentManager $dm,
    ) {
        $this->repository = $dm->getRepository(ClientDocument::class);
    }

    public function save(Client $client): void
    {
        $doc = $this->repository->findOneBy(['clientId' => $client->id()->value()]);
        if ($doc === null) {
            $doc = new ClientDocument();
            $doc->clientId = $client->id()->value();
            $doc->createdAt = \DateTime::createFromImmutable($client->createdAt());
            $this->dm->persist($doc);
        }

        $doc->nip = $client->nip()->value();
        $doc->name = $client->name();
        $doc->address = $client->address();
        $doc->country = $client->country();
        $doc->email = $client->email();
        $doc->description = $client->description();
        $doc->status = $client->status();
        $doc->contacts = array_map(fn(Contact $c) => [
            'id' => $c->id(),
            'firstName' => $c->firstName(),
            'lastName' => $c->lastName(),
            'email' => $c->email(),
            'phone' => $c->phone(),
        ], $client->contacts());
        $doc->updatedAt = \DateTime::createFromImmutable($client->updatedAt());

        $this->dm->flush();
    }

    public function findById(ClientId $id): ?Client
    {
        $doc = $this->repository->findOneBy(['clientId' => $id->value()]);
        return $doc ? $this->toDomain($doc) : null;
    }

    public function findByNip(ClientNip $nip): ?Client
    {
        $doc = $this->repository->findOneBy(['nip' => $nip->value()]);
        return $doc ? $this->toDomain($doc) : null;
    }

    public function findAll(): array
    {
        $docs = $this->repository->findAll();
        return array_map(fn(ClientDocument $doc) => $this->toDomain($doc), $docs);
    }

    public function searchByTerm(string $term): array
    {
        $regex = new \MongoDB\BSON\Regex(preg_quote($term), 'i');
        $docs = $this->repository->createQueryBuilder()
            ->addOr($this->repository->createQueryBuilder()->field('name')->equals($regex))
            ->addOr($this->repository->createQueryBuilder()->field('email')->equals($regex))
            ->addOr($this->repository->createQueryBuilder()->field('nip')->equals($regex))
            ->getQuery()
            ->execute();

        return array_map(fn(ClientDocument $doc) => $this->toDomain($doc), iterator_to_array($docs));
    }

    public function nextIdentity(): ClientId
    {
        return ClientId::generate();
    }

    private function toDomain(ClientDocument $doc): Client
    {
        $client = new \ReflectionClass(Client::class);
        $instance = $client->newInstanceWithoutConstructor();

        $idProp = $client->getProperty('id');
        $idProp->setAccessible(true);
        $idProp->setValue($instance, new ClientId($doc->clientId));

        $nipProp = $client->getProperty('nip');
        $nipProp->setAccessible(true);
        $nipProp->setValue($instance, new ClientNip($doc->nip));

        $nameProp = $client->getProperty('name');
        $nameProp->setAccessible(true);
        $nameProp->setValue($instance, $doc->name);

        $addrProp = $client->getProperty('address');
        $addrProp->setAccessible(true);
        $addrProp->setValue($instance, $doc->address);

        $countryProp = $client->getProperty('country');
        $countryProp->setAccessible(true);
        $countryProp->setValue($instance, $doc->country);

        $emailProp = $client->getProperty('email');
        $emailProp->setAccessible(true);
        $emailProp->setValue($instance, $doc->email);

        $descProp = $client->getProperty('description');
        $descProp->setAccessible(true);
        $descProp->setValue($instance, $doc->description);

        $statusProp = $client->getProperty('status');
        $statusProp->setAccessible(true);
        $statusProp->setValue($instance, $doc->status);

        $contactsProp = $client->getProperty('contacts');
        $contactsProp->setAccessible(true);
        $contactsProp->setValue($instance, array_map(
            fn(array $c) => Contact::create(
                $c['firstName'],
                $c['lastName'],
                $c['email'],
                $c['phone'] ?? null,
            ),
            $doc->contacts,
        ));

        $createdProp = $client->getProperty('createdAt');
        $createdProp->setAccessible(true);
        $createdProp->setValue($instance, \DateTimeImmutable::createFromMutable($doc->createdAt));

        $updatedProp = $client->getProperty('updatedAt');
        $updatedProp->setAccessible(true);
        $updatedProp->setValue($instance, \DateTimeImmutable::createFromMutable($doc->updatedAt));

        return $instance;
    }
}

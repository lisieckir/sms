<?php

declare(strict_types=1);

namespace App\ClientManagement\Infrastructure\DataFixtures;

use App\ClientManagement\Domain\Model\Client;
use App\ClientManagement\Domain\Model\ClientId;
use App\ClientManagement\Domain\Model\ClientNip;
use App\ClientManagement\Domain\Model\ClientRepositoryInterface;
use Doctrine\Bundle\MongoDBBundle\Fixture\Fixture;
use Doctrine\Persistence\ObjectManager;

final class ClientFixtures extends Fixture
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $clients = [
            ['id' => 'acme', 'nip' => '1234567890', 'name' => 'Acme Corp', 'address' => 'ul. Marszałkowska 100, 00-001 Warszawa', 'country' => 'Poland', 'email' => 'kontakt@acme.pl', 'description' => 'Leading software development company', 'contacts' => [
                ['firstName' => 'Jan', 'lastName' => 'Kowalski', 'email' => 'j.kowalski@acme.pl', 'phone' => '+48 601 111 111'],
                ['firstName' => 'Anna', 'lastName' => 'Nowak', 'email' => 'a.nowak@acme.pl', 'phone' => null],
            ]],
            ['id' => 'globex', 'nip' => '2345678901', 'name' => 'Globex Inc', 'address' => 'ul. Długa 50, 30-001 Kraków', 'country' => 'Poland', 'email' => 'info@globex.pl', 'description' => 'E-commerce platform provider', 'contacts' => [
                ['firstName' => 'Piotr', 'lastName' => 'Wiśniewski', 'email' => 'p.wisniewski@globex.pl', 'phone' => '+48 602 222 222'],
            ]],
            ['id' => 'initech', 'nip' => '3456789012', 'name' => 'Initech', 'address' => 'ul. Piotrkowska 200, 90-001 Łódź', 'country' => 'Poland', 'email' => 'hello@initech.pl', 'description' => 'Financial services and consulting', 'contacts' => [
                ['firstName' => 'Michał', 'lastName' => 'Zieliński', 'email' => 'm.zielinski@initech.pl', 'phone' => '+48 603 333 333'],
                ['firstName' => 'Katarzyna', 'lastName' => 'Lewandowska', 'email' => 'k.lewandowska@initech.pl', 'phone' => '+48 604 444 444'],
            ]],
            ['id' => 'umbrella', 'nip' => '4567890123', 'name' => 'Umbrella Co', 'address' => 'al. Grunwaldzka 500, 80-001 Gdańsk', 'country' => 'Poland', 'email' => 'office@umbrella.pl', 'description' => 'Healthcare and pharmaceutical research', 'contacts' => [
                ['firstName' => 'Tomasz', 'lastName' => 'Kamiński', 'email' => 't.kaminski@umbrella.pl', 'phone' => null],
            ]],
        ];

        foreach ($clients as $data) {
            $id = ClientId::generate();
            $nip = new ClientNip($data['nip']);

            $client = Client::register(
                id: $id,
                nip: $nip,
                name: $data['name'],
                address: $data['address'],
                country: $data['country'],
                email: $data['email'],
                description: $data['description'],
            );

            foreach ($data['contacts'] as $contact) {
                $client->addContact($contact['firstName'], $contact['lastName'], $contact['email'], $contact['phone']);
            }

            $this->clientRepository->save($client);
        }
    }
}

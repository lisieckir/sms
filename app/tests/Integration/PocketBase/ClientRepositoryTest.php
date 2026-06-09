<?php

declare(strict_types=1);

namespace App\Tests\Integration\PocketBase;

use App\ClientManagement\Domain\Model\Client;
use App\ClientManagement\Domain\Model\ClientId;
use App\ClientManagement\Domain\Model\ClientNip;
use App\ClientManagement\Infrastructure\PocketBase\ClientRepository;
use App\Core\Infrastructure\PocketBase\PocketBaseClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\HttpClient;

/**
 * @group integration
 */
final class ClientRepositoryTest extends TestCase
{
    private static ?PocketBaseClient $pb = null;
    private ClientRepository $repository;

    public static function setUpBeforeClass(): void
    {
        $url = $_ENV['POCKETBASE_URL'] ?? 'http://localhost:8090';
        try {
            $httpClient = HttpClient::create();
            $pb = new PocketBaseClient($httpClient, $url);
            $pb->list('clients');
            self::$pb = $pb;
        } catch (\Throwable) {
            self::markTestSkipped('PocketBase is not available at ' . $url);
        }
    }

    protected function setUp(): void
    {
        $this->repository = new ClientRepository(self::$pb);
    }

    public function testSaveAndFindById(): void
    {
        $id = ClientId::generate();
        $nip = new ClientNip('1234567890');
        $client = Client::register($id, $nip, 'Test Corp', '123 Main St', 'USA', 'test@corp.com', 'A test client');
        $client->addContact('John', 'Doe', 'john@corp.com', '+123456789');

        $this->repository->save($client);

        $found = $this->repository->findById($id);
        $this->assertNotNull($found);
        $this->assertSame('Test Corp', $found->name());
        $this->assertCount(1, $found->contacts());
    }

    public function testFindByNip(): void
    {
        $nipValue = '9876543210';
        $id = ClientId::generate();
        $client = Client::register($id, new ClientNip($nipValue), 'NIP Corp', '456 Oak St', 'Poland', 'nip@corp.com', '');
        $this->repository->save($client);

        $found = $this->repository->findByNip(new ClientNip($nipValue));
        $this->assertNotNull($found);
        $this->assertSame('NIP Corp', $found->name());
    }
}

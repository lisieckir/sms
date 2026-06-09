<?php

declare(strict_types=1);

namespace App\Tests\Integration\PocketBase;

use App\Core\Infrastructure\PocketBase\PocketBaseClient;
use App\IdentityAccess\Domain\Model\User;
use App\IdentityAccess\Domain\Model\UserEmail;
use App\IdentityAccess\Domain\Model\UserId;
use App\IdentityAccess\Domain\Model\UserName;
use App\IdentityAccess\Domain\Model\UserPassword;
use App\IdentityAccess\Infrastructure\PocketBase\UserRepository;
use App\Tests\InMemory\InMemoryPasswordHasher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\HttpClient;

/**
 * @group integration
 */
final class UserRepositoryTest extends TestCase
{
    private static ?PocketBaseClient $pb = null;
    private UserRepository $repository;
    private InMemoryPasswordHasher $hasher;

    public static function setUpBeforeClass(): void
    {
        $url = $_ENV['POCKETBASE_URL'] ?? 'http://localhost:8090';
        try {
            $httpClient = HttpClient::create();
            $pb = new PocketBaseClient($httpClient, $url);
            $pb->list('app_users');
            self::$pb = $pb;
        } catch (\Throwable) {
            self::markTestSkipped('PocketBase is not available at ' . $url);
        }
    }

    protected function setUp(): void
    {
        $this->repository = new UserRepository(self::$pb);
        $this->hasher = new InMemoryPasswordHasher();
    }

    public function testSaveAndFindById(): void
    {
        $id = UserId::generate();
        $name = new UserName('John', 'Doe');
        $email = new UserEmail('john@example.com');
        $password = new UserPassword('plainpass');
        $user = User::register($id, $name, $email, 'johndoe', $password, $this->hasher);

        $this->repository->save($user);

        $found = $this->repository->findById($id);
        $this->assertNotNull($found);
        $this->assertSame('John', $found->name()->firstName());
        $this->assertSame('john@example.com', $found->email()->value());
    }

    public function testFindByEmail(): void
    {
        $id = UserId::generate();
        $user = User::register(
            $id,
            new UserName('Jane', 'Doe'),
            new UserEmail('jane@example.com'),
            'janedoe',
            new UserPassword('pass'),
            $this->hasher,
        );
        $this->repository->save($user);

        $found = $this->repository->findByEmail(new UserEmail('jane@example.com'));
        $this->assertNotNull($found);
        $this->assertSame('janedoe', $found->username());
    }

    public function testFindByUsername(): void
    {
        $id = UserId::generate();
        $user = User::register(
            $id,
            new UserName('Bob', 'Smith'),
            new UserEmail('bob@example.com'),
            'bobsmith',
            new UserPassword('pass'),
            $this->hasher,
        );
        $this->repository->save($user);

        $found = $this->repository->findByUsername('bobsmith');
        $this->assertNotNull($found);
        $this->assertSame('bob@example.com', $found->email()->value());
    }

    public function testFindAll(): void
    {
        $users = $this->repository->findAll();
        $this->assertIsArray($users);
    }

    public function testSearchByTerm(): void
    {
        $id = UserId::generate();
        $user = User::register(
            $id,
            new UserName('Alice', 'Wonderland'),
            new UserEmail('alice@example.com'),
            'alicew',
            new UserPassword('pass'),
            $this->hasher,
        );
        $this->repository->save($user);

        $results = $this->repository->searchByTerm('Alice');
        $this->assertGreaterThanOrEqual(1, count($results));
    }
}

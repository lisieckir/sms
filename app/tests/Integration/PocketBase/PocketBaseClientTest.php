<?php

declare(strict_types=1);

namespace App\Tests\Integration\PocketBase;

use App\Core\Infrastructure\PocketBase\PocketBaseClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\HttpClient;

/**
 * @group integration
 */
final class PocketBaseClientTest extends TestCase
{
    private static ?PocketBaseClient $client = null;

    public static function setUpBeforeClass(): void
    {
        $url = $_ENV['POCKETBASE_URL'] ?? 'http://localhost:8090';
        try {
            $httpClient = HttpClient::create();
            $testClient = new PocketBaseClient($httpClient, $url);
            $testClient->list('app_users');
            self::$client = $testClient;
        } catch (\Throwable) {
            self::markTestSkipped('PocketBase is not available at ' . $url);
        }
    }

    protected function getClient(): PocketBaseClient
    {
        return self::$client;
    }
}

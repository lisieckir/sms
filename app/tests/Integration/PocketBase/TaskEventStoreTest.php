<?php

declare(strict_types=1);

namespace App\Tests\Integration\PocketBase;

use App\Core\Infrastructure\PocketBase\PocketBaseClient;
use App\TaskManagement\Infrastructure\PocketBase\TaskEventStore;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Uid\Uuid;

/**
 * @group integration
 */
final class TaskEventStoreTest extends TestCase
{
    private static ?PocketBaseClient $pb = null;

    public static function setUpBeforeClass(): void
    {
        $url = $_ENV['POCKETBASE_URL'] ?? 'http://localhost:8090';
        try {
            $httpClient = HttpClient::create();
            $pb = new PocketBaseClient($httpClient, $url);
            $pb->list('task_events');
            self::$pb = $pb;
        } catch (\Throwable) {
            self::markTestSkipped('PocketBase is not available at ' . $url);
        }
    }

    protected function setUp(): void
    {
        $this->store = new TaskEventStore(self::$pb);
    }

    public function testAppendAndFindByTask(): void
    {
        $taskId = 'integration-test-task-' . Uuid::v4()->toRfc4122();

        $this->store->append(
            eventId: Uuid::v4()->toRfc4122(),
            taskId: $taskId,
            userId: 'user-1',
            type: 'TaskCreated',
            data: ['title' => 'Test Event Task'],
            occurredAt: new \DateTimeImmutable(),
        );

        $events = $this->store->findByTask($taskId);
        $this->assertCount(1, $events);
        $this->assertSame('TaskCreated', $events[0]['type']);
    }
}

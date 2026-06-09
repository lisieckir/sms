<?php

declare(strict_types=1);

namespace App\Tests\Integration\PocketBase;

use App\Core\Infrastructure\PocketBase\PocketBaseClient;
use App\TaskManagement\Domain\Model\Task;
use App\TaskManagement\Domain\Model\TaskDescription;
use App\TaskManagement\Domain\Model\TaskId;
use App\TaskManagement\Infrastructure\PocketBase\TaskRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\HttpClient;

/**
 * @group integration
 */
final class TaskRepositoryTest extends TestCase
{
    private static ?PocketBaseClient $pb = null;
    private TaskRepository $repository;

    public static function setUpBeforeClass(): void
    {
        $url = $_ENV['POCKETBASE_URL'] ?? 'http://localhost:8090';
        try {
            $httpClient = HttpClient::create();
            $pb = new PocketBaseClient($httpClient, $url);
            $pb->list('tasks');
            self::$pb = $pb;
        } catch (\Throwable) {
            self::markTestSkipped('PocketBase is not available at ' . $url);
        }
    }

    protected function setUp(): void
    {
        $this->repository = new TaskRepository(self::$pb);
    }

    public function testSaveAndFindById(): void
    {
        $id = TaskId::generate();
        $task = Task::create(
            id: $id,
            title: 'Test Task',
            description: new TaskDescription('A test task'),
            creatorId: 'user-1',
            stageId: 'stage-1',
            position: 100,
            assigneeId: null,
            clientId: null,
        );

        $this->repository->save($task);

        $found = $this->repository->findById($id);
        $this->assertNotNull($found);
        $this->assertSame('Test Task', $found->title());
        $this->assertSame('A test task', $found->description()->value());
    }

    public function testSaveWithComments(): void
    {
        $id = TaskId::generate();
        $task = Task::create(
            id: $id,
            title: 'Task with comment',
            description: new TaskDescription('Desc'),
            creatorId: 'user-1',
            stageId: 'stage-1',
            position: 100,
        );

        $task->addComment('user-2', 'This is a comment');
        $this->repository->save($task);

        $found = $this->repository->findById($id);
        $this->assertNotNull($found);
        $this->assertCount(1, $found->comments());
        $this->assertSame('This is a comment', $found->comments()[0]->content());
    }

    public function testFindByStage(): void
    {
        $id1 = TaskId::generate();
        $task1 = Task::create($id1, 'Task A', new TaskDescription(''), 'user-1', 'stage-x', 100);
        $this->repository->save($task1);

        $id2 = TaskId::generate();
        $task2 = Task::create($id2, 'Task B', new TaskDescription(''), 'user-1', 'stage-x', 200);
        $this->repository->save($task2);

        $tasks = $this->repository->findByStage('stage-x');
        $this->assertGreaterThanOrEqual(2, count($tasks));
    }

    public function testFindAll(): void
    {
        $tasks = $this->repository->findAll();
        $this->assertIsArray($tasks);
    }
}

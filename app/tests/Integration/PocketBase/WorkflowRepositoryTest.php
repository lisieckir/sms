<?php

declare(strict_types=1);

namespace App\Tests\Integration\PocketBase;

use App\BoardManagement\Domain\Model\Workflow;
use App\BoardManagement\Infrastructure\PocketBase\WorkflowRepository;
use App\Core\Infrastructure\PocketBase\PocketBaseClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\HttpClient;

/**
 * @group integration
 */
final class WorkflowRepositoryTest extends TestCase
{
    private static ?PocketBaseClient $pb = null;
    private WorkflowRepository $repository;

    public static function setUpBeforeClass(): void
    {
        $url = $_ENV['POCKETBASE_URL'] ?? 'http://localhost:8090';
        try {
            $httpClient = HttpClient::create();
            $pb = new PocketBaseClient($httpClient, $url);
            $pb->list('workflows');
            self::$pb = $pb;
        } catch (\Throwable) {
            self::markTestSkipped('PocketBase is not available at ' . $url);
        }
    }

    protected function setUp(): void
    {
        $this->repository = new WorkflowRepository(self::$pb);
    }

    public function testSaveAndFindById(): void
    {
        $id = $this->repository->nextIdentity();
        $workflow = Workflow::create($id, 'Test Workflow', isDefault: false);
        $workflow->addStage('To Do', 0);
        $workflow->addStage('Done', 1);

        $stages = $workflow->sortedStages();
        $workflow->addTransition($stages[0]->id(), $stages[1]->id());

        $this->repository->save($workflow);

        $found = $this->repository->findById($id);
        $this->assertNotNull($found);
        $this->assertSame('Test Workflow', $found->name());
        $this->assertCount(2, $found->stages());
        $this->assertCount(1, $found->transitions());
    }

    public function testFindDefault(): void
    {
        $default = $this->repository->findDefault();
        $this->assertNotNull($default);
        $this->assertTrue($default->isDefault());
        $this->assertSame('Default Workflow', $default->name());
    }
}

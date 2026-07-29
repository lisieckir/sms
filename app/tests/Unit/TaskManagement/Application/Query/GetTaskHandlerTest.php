<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskManagement\Application\Query;

use App\BoardManagement\Domain\Model\Workflow;
use App\BoardManagement\Domain\Model\WorkflowId;
use App\Core\UI\Twig\MarkdownConverter;
use App\IdentityAccess\Domain\Model\User;
use App\IdentityAccess\Domain\Model\UserEmail;
use App\IdentityAccess\Domain\Model\UserId;
use App\IdentityAccess\Domain\Model\UserName;
use App\IdentityAccess\Domain\Model\UserPassword;
use App\TaskManagement\Application\DTO\TaskDTO;
use App\TaskManagement\Application\Query\GetTaskHandler;
use App\TaskManagement\Application\Query\GetTaskQuery;
use App\TaskManagement\Domain\Model\Task;
use App\TaskManagement\Domain\Model\TaskDescription;
use App\TaskManagement\Domain\Model\TaskId;
use App\Tests\InMemory\InMemoryClientRepository;
use App\Tests\InMemory\InMemoryPasswordHasher;
use App\Tests\InMemory\InMemoryTaskRepository;
use App\Tests\InMemory\InMemoryUserRepository;
use App\Tests\InMemory\InMemoryWorkflowRepository;
use PHPUnit\Framework\TestCase;

class GetTaskHandlerTest extends TestCase
{
    private InMemoryTaskRepository $taskRepository;
    private InMemoryWorkflowRepository $workflowRepository;
    private InMemoryUserRepository $userRepository;
    private InMemoryClientRepository $clientRepository;
    private MarkdownConverter $markdownConverter;
    private GetTaskHandler $handler;
    private string $userId;

    protected function setUp(): void
    {
        $this->taskRepository = new InMemoryTaskRepository();
        $this->workflowRepository = new InMemoryWorkflowRepository();
        $this->userRepository = new InMemoryUserRepository();
        $this->clientRepository = new InMemoryClientRepository();
        $this->markdownConverter = new MarkdownConverter();
        $this->handler = new GetTaskHandler(
            $this->taskRepository,
            $this->workflowRepository,
            $this->userRepository,
            $this->clientRepository,
            $this->markdownConverter,
        );
        $this->userId = UserId::generate()->value();
    }

    public function testGetTaskReturnsTaskDTO(): void
    {
        $task = $this->createSavedTask();
        $dto = $this->handler->__invoke(new GetTaskQuery($task->id()->value()));

        $this->assertInstanceOf(TaskDTO::class, $dto);
        $this->assertSame('Test Task', $dto->title());
        $this->assertSame('Hello **world**', $dto->description());
        $this->assertStringContainsString('<strong>world</strong>', $dto->descriptionHtml());
    }

    public function testGetTaskReturnsNullWhenNotFound(): void
    {
        $result = $this->handler->__invoke(new GetTaskQuery('00000000-0000-0000-0000-000000000000'));
        $this->assertNull($result);
    }

    public function testGetTaskResolvesStageName(): void
    {
        $workflow = Workflow::create(WorkflowId::generate(), 'Default', true);
        $workflow->addStage('Backlog', 0);
        $workflow->releaseEvents();
        $this->workflowRepository->save($workflow);

        $stageId = $this->workflowRepository->findDefault()->sortedStages()[0]->id();
        $task = $this->createSavedTask($stageId);

        $dto = $this->handler->__invoke(new GetTaskQuery($task->id()->value()));

        $this->assertSame('Backlog', $dto->stageName());
    }

    public function testGetTaskResolvesAssigneeName(): void
    {
        $this->createSavedUser();
        $task = $this->createSavedTask(assigneeId: $this->userId);

        $dto = $this->handler->__invoke(new GetTaskQuery($task->id()->value()));

        $this->assertSame('johndoe', $dto->assigneeName());
    }

    public function testGetTaskResolvesClientName(): void
    {
        $clientId = \App\ClientManagement\Domain\Model\ClientId::generate();
        $client = \App\ClientManagement\Domain\Model\Client::register(
            $clientId,
            new \App\ClientManagement\Domain\Model\ClientNip('1234567890'),
            'Acme Corp',
            '123 Main St',
            'US',
        );
        $client->releaseEvents();
        $this->clientRepository->save($client);

        $task = $this->createSavedTask(clientId: $clientId->value());

        $dto = $this->handler->__invoke(new GetTaskQuery($task->id()->value()));

        $this->assertSame('Acme Corp', $dto->clientName());
    }

    public function testGetTaskReturnsCommentsWithUserNames(): void
    {
        $this->createSavedUser();
        $task = $this->createSavedTask();
        $task->addComment($this->userId, 'Nice work!');
        $task->releaseEvents();
        $this->taskRepository->save($task);

        $dto = $this->handler->__invoke(new GetTaskQuery($task->id()->value()));

        $this->assertCount(1, $dto->comments());
        $this->assertSame('Nice work!', $dto->comments()[0]['content']);
    }

    public function testGetTaskReturnsMultilineComments(): void
    {
        $this->createSavedUser();
        $task = $this->createSavedTask();
        $multiline = "Line one\nLine two\n\nLine four";
        $task->addComment($this->userId, $multiline);
        $task->releaseEvents();
        $this->taskRepository->save($task);

        $dto = $this->handler->__invoke(new GetTaskQuery($task->id()->value()));

        $this->assertCount(1, $dto->comments());
        $this->assertSame($multiline, $dto->comments()[0]['content']);
        $this->assertStringContainsString("\n", $dto->comments()[0]['content']);
    }

    public function testGetTaskReturnsWorklogs(): void
    {
        $this->createSavedUser();
        $task = $this->createSavedTask();
        $task->addWorklog($this->userId, 60, 'Worked on feature');
        $task->releaseEvents();
        $this->taskRepository->save($task);

        $dto = $this->handler->__invoke(new GetTaskQuery($task->id()->value()));

        $this->assertCount(1, $dto->worklogs());
        $this->assertSame(60, $dto->worklogs()[0]['minutes']);
    }

    public function testGetTaskReturnsSubtasks(): void
    {
        $parent = $this->createSavedTask(title: 'Parent');
        $childId = TaskId::generate();
        $child = Task::create(
            $childId,
            'Child',
            new TaskDescription('Desc'),
            'creator-1',
            'stage-1',
            0,
            null,
            null,
            $parent->id()->value(),
        );
        $child->releaseEvents();
        $this->taskRepository->save($child);

        $dto = $this->handler->__invoke(new GetTaskQuery($parent->id()->value()));

        $this->assertCount(1, $dto->subtasks());
        $this->assertSame('Child', $dto->subtasks()[0]['title']);
    }

    public function testGetTaskReturnsParentTaskName(): void
    {
        $parent = $this->createSavedTask(title: 'Parent');
        $childId = TaskId::generate();
        $child = Task::create(
            $childId,
            'Child',
            new TaskDescription('Desc'),
            'creator-1',
            'stage-1',
            0,
            null,
            null,
            $parent->id()->value(),
        );
        $child->releaseEvents();
        $this->taskRepository->save($child);

        $dto = $this->handler->__invoke(new GetTaskQuery($child->id()->value()));

        $this->assertSame('Parent', $dto->parentTaskName());
    }

    public function testGetTaskSumChildTimeSpent(): void
    {
        $parentId = TaskId::generate();
        $parent = Task::create(
            $parentId,
            'Parent',
            new TaskDescription('Parent task'),
            'creator-1',
            'stage-1',
            0,
        );
        $parent->releaseEvents();
        $this->taskRepository->save($parent);

        $child1 = Task::create(
            TaskId::generate(),
            'Child 1',
            new TaskDescription('Desc'),
            'creator-1',
            'stage-1',
            0,
            null,
            null,
            $parentId->value(),
        );
        $child1->addWorklog($this->userId, 30, 'Work on child 1');
        $child1->releaseEvents();
        $this->taskRepository->save($child1);

        $child2 = Task::create(
            TaskId::generate(),
            'Child 2',
            new TaskDescription('Desc'),
            'creator-1',
            'stage-1',
            0,
            null,
            null,
            $parentId->value(),
        );
        $child2->addWorklog($this->userId, 45, 'Work on child 2');
        $child2->releaseEvents();
        $this->taskRepository->save($child2);

        $dto = $this->handler->__invoke(new GetTaskQuery($parentId->value()));

        $this->assertSame(75, $dto->totalTimeSpent());
    }

    private function createSavedTask(
        string $stageId = 'stage-1',
        ?string $assigneeId = null,
        ?string $clientId = null,
        string $title = 'Test Task',
    ): Task {
        $task = Task::create(
            TaskId::generate(),
            $title,
            new TaskDescription('Hello **world**'),
            'creator-1',
            $stageId,
            0,
            $assigneeId,
            $clientId,
        );
        $task->releaseEvents();
        $this->taskRepository->save($task);
        return $task;
    }

    private function createSavedUser(): User
    {
        $user = User::register(
            new UserId($this->userId),
            new UserName('John', 'Doe'),
            new UserEmail('john@example.com'),
            'johndoe',
            new UserPassword('hashed-pass'),
            new InMemoryPasswordHasher(),
        );
        $user->releaseEvents();
        $this->userRepository->save($user);
        return $user;
    }
}

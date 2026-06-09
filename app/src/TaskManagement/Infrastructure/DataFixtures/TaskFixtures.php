<?php

declare(strict_types=1);

namespace App\TaskManagement\Infrastructure\DataFixtures;

use App\TaskManagement\Domain\Model\Task;
use App\TaskManagement\Domain\Model\TaskDescription;
use App\TaskManagement\Domain\Model\TaskRepositoryInterface;
use App\TaskManagement\Infrastructure\Projection\TaskEventProjector;
use App\ClientManagement\Domain\Model\ClientNip;
use App\ClientManagement\Domain\Model\ClientRepositoryInterface;
use App\BoardManagement\Domain\Model\WorkflowRepositoryInterface;
use App\IdentityAccess\Domain\Model\UserRepositoryInterface;

final class TaskFixtures
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private TaskEventProjector $eventProjector,
        private UserRepositoryInterface $userRepository,
        private ClientRepositoryInterface $clientRepository,
        private WorkflowRepositoryInterface $workflowRepository,
    ) {}

    public function load(): void
    {
        if (!empty($this->taskRepository->findAll())) {
            return;
        }

        $workflow = $this->workflowRepository->findDefault();
        if ($workflow === null) {
            throw new \RuntimeException('Default workflow not found — run WorkflowFixtures first');
        }
        $stages = $workflow->sortedStages();

        $adminId = $this->userRepository->findByUsername('admin')?->id()->value()
            ?? throw new \RuntimeException('admin user not found');
        $aliceId = $this->userRepository->findByUsername('alice')?->id()->value()
            ?? throw new \RuntimeException('alice user not found');
        $bobId = $this->userRepository->findByUsername('bob')?->id()->value()
            ?? throw new \RuntimeException('bob user not found');
        $carolId = $this->userRepository->findByUsername('carol')?->id()->value()
            ?? throw new \RuntimeException('carol user not found');
        $daveId = $this->userRepository->findByUsername('dave')?->id()->value()
            ?? throw new \RuntimeException('dave user not found');

        $acmeId = $this->clientRepository->findByNip(new ClientNip('1234567890'))?->id()->value()
            ?? throw new \RuntimeException('Acme Corp client not found');
        $globexId = $this->clientRepository->findByNip(new ClientNip('2345678901'))?->id()->value()
            ?? throw new \RuntimeException('Globex Inc client not found');
        $initechId = $this->clientRepository->findByNip(new ClientNip('3456789012'))?->id()->value()
            ?? throw new \RuntimeException('Initech client not found');

        $backlog = $stages[0];
        $todo = $stages[1];
        $inProgress = $stages[2];
        $review = $stages[3];
        $done = $stages[4];

        $tasks = [
            ['title' => 'Set up CI/CD pipeline', 'desc' => 'Configure GitHub Actions for automated testing and deployment to staging environment.', 'stage' => $backlog, 'pos' => 100, 'creator' => $adminId, 'assignee' => $daveId, 'client' => null],
            ['title' => 'Design new landing page', 'desc' => 'Create mockups and wireframes for the redesigned landing page with improved conversion funnel.', 'stage' => $backlog, 'pos' => 200, 'creator' => $aliceId, 'assignee' => null, 'client' => $acmeId],
            ['title' => 'Implement user onboarding flow', 'desc' => 'Build step-by-step onboarding wizard with tooltips and progress tracking for new users.', 'stage' => $todo, 'pos' => 100, 'creator' => $adminId, 'assignee' => $bobId, 'client' => null, 'comments' => [
                ['user' => $adminId, 'text' => 'Please check the current onboarding prototype first'],
            ]],
            ['title' => 'Add export to CSV feature', 'desc' => 'Allow users to export all task data as CSV from the board view and task detail page.', 'stage' => $todo, 'pos' => 200, 'creator' => $carolId, 'assignee' => $aliceId, 'client' => $globexId],
            ['title' => 'Fix pagination on client list', 'desc' => 'Pagination breaks when filtering clients by status. The total count is not recalculated.', 'stage' => $todo, 'pos' => 300, 'creator' => $bobId, 'assignee' => null, 'client' => null],
            ['title' => 'Build REST API for mobile app', 'desc' => 'Design and implement the REST API endpoints needed for the upcoming mobile app v2.', 'stage' => $inProgress, 'pos' => 100, 'creator' => $adminId, 'assignee' => $daveId, 'client' => $initechId, 'comments' => [
                ['user' => $daveId, 'text' => 'Working on authentication endpoints this week'],
                ['user' => $adminId, 'text' => 'Make sure to include rate limiting'],
            ], 'worklogs' => [
                ['user' => $daveId, 'minutes' => 480, 'desc' => 'Implemented OAuth2 token endpoint'],
                ['user' => $daveId, 'minutes' => 240, 'desc' => 'Added input validation for all POST endpoints'],
            ]],
            ['title' => 'Migrate legacy database', 'desc' => 'Plan and execute migration of legacy MySQL data to the new MongoDB schema.', 'stage' => $inProgress, 'pos' => 200, 'creator' => $carolId, 'assignee' => $bobId, 'client' => null, 'worklogs' => [
                ['user' => $bobId, 'minutes' => 360, 'desc' => 'Data mapping analysis for users collection'],
            ]],
            ['title' => 'Update privacy policy page', 'desc' => 'Update the privacy policy to comply with the latest GDPR requirements and cookie regulations.', 'stage' => $review, 'pos' => 100, 'creator' => $aliceId, 'assignee' => $carolId, 'client' => null, 'comments' => [
                ['user' => $carolId, 'text' => 'Legal team approved the draft, just need final review'],
            ]],
            ['title' => 'Performance audit report', 'desc' => 'Run Lighthouse audit and generate performance report with recommendations for optimization.', 'stage' => $review, 'pos' => 200, 'creator' => $adminId, 'assignee' => $aliceId, 'client' => $acmeId, 'comments' => [
                ['user' => $aliceId, 'text' => 'Initial results show 72 on mobile, targeting 90+'],
            ], 'worklogs' => [
                ['user' => $aliceId, 'minutes' => 180, 'desc' => 'Ran full audit suite and documented findings'],
            ]],
            ['title' => 'Deploy v1.0 to production', 'desc' => 'Final deployment checklist: run migrations, verify all services, update DNS records.', 'stage' => $done, 'pos' => 100, 'creator' => $adminId, 'assignee' => $adminId, 'client' => null, 'comments' => [
                ['user' => $adminId, 'text' => 'Deployment completed successfully at 2026-06-01 14:30 UTC'],
                ['user' => $bobId, 'text' => 'All smoke tests passing in production'],
            ], 'worklogs' => [
                ['user' => $adminId, 'minutes' => 120, 'desc' => 'Production deployment and verification'],
                ['user' => $bobId, 'minutes' => 60, 'desc' => 'Post-deployment smoke tests'],
            ]],
        ];

        foreach ($tasks as $data) {
            $id = $this->taskRepository->nextIdentity();
            $task = Task::create(
                id: $id,
                title: $data['title'],
                description: new TaskDescription($data['desc']),
                creatorId: $data['creator'],
                stageId: $data['stage']->id(),
                position: $data['pos'],
                assigneeId: $data['assignee'],
                clientId: $data['client'],
            );

            if (isset($data['comments'])) {
                foreach ($data['comments'] as $c) {
                    $task->addComment($c['user'], $c['text']);
                }
            }

            if (isset($data['worklogs'])) {
                foreach ($data['worklogs'] as $w) {
                    $task->addWorklog($w['user'], $w['minutes'], $w['desc']);
                }
            }

            $this->taskRepository->save($task);

            foreach ($task->releaseEvents() as $event) {
                $this->eventProjector->project($event);
            }
        }
    }
}

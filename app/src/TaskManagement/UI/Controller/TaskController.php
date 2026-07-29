<?php

declare(strict_types=1);

namespace App\TaskManagement\UI\Controller;

use App\BoardManagement\Application\Query\GetBoardHandler;
use App\BoardManagement\Application\Query\GetBoardQuery;
use App\BoardManagement\Domain\Model\WorkflowRepositoryInterface;
use App\ClientManagement\Application\Query\ListClientsHandler;
use App\ClientManagement\Application\Query\ListClientsQuery;
use App\IdentityAccess\Domain\Model\UserRepositoryInterface;
use App\TaskManagement\Application\Command\AddComment\AddCommentCommand;
use App\TaskManagement\Application\Command\AddWorklog\AddWorklogCommand;
use App\TaskManagement\Application\Command\AssignClient\AssignClientCommand;
use App\TaskManagement\Application\Command\AssignTask\AssignTaskCommand;
use App\TaskManagement\Application\Command\CreateTask\CreateTaskCommand;
use App\TaskManagement\Application\Command\DeleteComment\DeleteCommentCommand;
use App\TaskManagement\Application\Command\EditComment\EditCommentCommand;
use App\TaskManagement\Application\Command\MoveTask\MoveTaskCommand;
use App\TaskManagement\Application\Query\GetTaskHandler;
use App\TaskManagement\Application\Query\GetTaskQuery;
use App\TaskManagement\Infrastructure\PocketBase\TaskEventStore;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class TaskController extends AbstractController
{
    #[Route('/task/create', name: 'app_task_create')]
    public function create(
        Request $request,
        MessageBusInterface $commandBus,
        GetBoardHandler $getBoard,
        ListClientsHandler $clientHandler,
    ): Response {
        $stages = $getBoard(new GetBoardQuery());
        $clients = $clientHandler(new ListClientsQuery());

        if ($request->isMethod('POST')) {
            $commandBus->dispatch(new CreateTaskCommand(
                title: $request->request->get('title'),
                description: $request->request->get('description', ''),
                creatorId: $this->getUser()->getUserId(),
                stageId: $request->request->get('stageId'),
                position: (int) $request->request->get('position', 0),
                priority: $request->request->get('priority') ?: null,
                assigneeId: $request->request->get('assigneeId') ?: null,
                clientId: $request->request->get('clientId') ?: null,
            ));

            $this->addFlash('success', 'Task created');
            if ($request->headers->get('HX-Request')) {
                $response = $this->redirectToRoute('app_board');
                $response->headers->set('HX-Redirect', $this->generateUrl('app_board'));
                return $response;
            }
            return $this->redirectToRoute('app_board');
        }

        return $this->render('task/form.html.twig', [
            'stages' => $stages,
            'clients' => $clients,
        ]);
    }

    #[Route('/task/create-modal', name: 'app_task_create_modal')]
    public function createModal(GetBoardHandler $getBoard, ListClientsHandler $clientHandler): Response
    {
        $stages = $getBoard(new GetBoardQuery());
        $clients = $clientHandler(new ListClientsQuery());

        return $this->render('task/form_modal.html.twig', [
            'stages' => $stages,
            'clients' => $clients,
        ]);
    }

    #[Route('/task/{id}', name: 'app_task_show')]
    public function show(string $id, GetTaskHandler $handler): Response
    {
        $task = $handler(new GetTaskQuery($id));
        if ($task === null) {
            throw $this->createNotFoundException('Task not found');
        }

        return $this->render('task/show.html.twig', [
            'task' => $task,
        ]);
    }

    #[Route('/task/{id}/comment', name: 'app_task_comment', methods: ['POST'])]
    public function addComment(
        string $id,
        Request $request,
        MessageBusInterface $commandBus,
    ): Response {
        $commandBus->dispatch(new AddCommentCommand(
            taskId: $id,
            userId: $this->getUser()->getUserId(),
            content: $request->request->get('content'),
        ));

        $this->addFlash('success', 'Comment added');
        return $this->redirectToRoute('app_task_show', ['id' => $id]);
    }

    #[Route('/task/{id}/comment/{commentId}/edit', name: 'app_task_comment_edit', methods: ['POST'])]
    public function editComment(
        string $id,
        string $commentId,
        Request $request,
        MessageBusInterface $commandBus,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $commandBus->dispatch(new EditCommentCommand(
            taskId: $id,
            commentId: $commentId,
            content: $request->request->get('content'),
        ));

        $this->addFlash('success', 'Comment edited');
        return $this->redirectToRoute('app_task_show', ['id' => $id]);
    }

    #[Route('/task/{id}/comment/{commentId}/delete', name: 'app_task_comment_delete', methods: ['POST'])]
    public function deleteComment(
        string $id,
        string $commentId,
        MessageBusInterface $commandBus,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $commandBus->dispatch(new DeleteCommentCommand(
            taskId: $id,
            commentId: $commentId,
        ));

        $this->addFlash('success', 'Comment deleted');
        return $this->redirectToRoute('app_task_show', ['id' => $id]);
    }

    #[Route('/task/{id}/worklog', name: 'app_task_worklog', methods: ['POST'])]
    public function addWorklog(
        string $id,
        Request $request,
        MessageBusInterface $commandBus,
    ): Response {
        $commandBus->dispatch(new AddWorklogCommand(
            taskId: $id,
            userId: $this->getUser()->getUserId(),
            minutes: (int) $request->request->get('minutes'),
            description: $request->request->get('description', ''),
        ));

        $this->addFlash('success', 'Worklog added');
        return $this->redirectToRoute('app_task_show', ['id' => $id]);
    }

    #[Route('/task/{id}/assign', name: 'app_task_assign')]
    public function assign(
        string $id,
        Request $request,
        MessageBusInterface $commandBus,
        UserRepositoryInterface $userRepository,
        GetTaskHandler $handler,
    ): Response {
        if ($request->isMethod('POST')) {
            $commandBus->dispatch(new AssignTaskCommand(
                taskId: $id,
                assigneeId: $request->request->get('assigneeId') ?: null,
            ));

            $this->addFlash('success', 'Task assigned');
            return $this->redirectToRoute('app_task_show', ['id' => $id]);
        }

        $task = $handler(new GetTaskQuery($id));
        if ($task === null) {
            throw $this->createNotFoundException('Task not found');
        }
        $users = $userRepository->findAll();

        return $this->render('task/assign.html.twig', [
            'taskId' => $id,
            'users' => $users,
            'currentAssigneeId' => $task->assigneeId(),
        ]);
    }

    #[Route('/task/{id}/client', name: 'app_task_assign_client', methods: ['POST'])]
    public function assignClient(
        string $id,
        Request $request,
        MessageBusInterface $commandBus,
    ): Response {
        $commandBus->dispatch(new AssignClientCommand(
            taskId: $id,
            clientId: $request->request->get('clientId') ?: null,
        ));

        return $this->redirectToRoute('app_task_show', ['id' => $id]);
    }

    #[Route('/task/{id}/subtask', name: 'app_task_create_subtask', methods: ['POST'])]
    public function createSubtask(
        string $id,
        Request $request,
        MessageBusInterface $commandBus,
        GetTaskHandler $handler,
    ): Response {
        $task = $handler(new GetTaskQuery($id));
        if ($task === null) {
            throw $this->createNotFoundException('Task not found');
        }

        $commandBus->dispatch(new \App\TaskManagement\Application\Command\CreateTask\CreateTaskCommand(
            title: $request->request->get('title'),
            description: '',
            creatorId: $this->getUser()->getUserId(),
            stageId: $task->stageId(),
            position: 0,
            assigneeId: null,
            clientId: null,
            parentTaskId: $id,
        ));

        $this->addFlash('success', 'Subticket created');
        return $this->redirectToRoute('app_task_show', ['id' => $id]);
    }

    #[Route('/task/{id}/description', name: 'app_task_edit_description', methods: ['POST'])]
    public function editDescription(
        string $id,
        Request $request,
        MessageBusInterface $commandBus,
    ): Response {
        $commandBus->dispatch(new \App\TaskManagement\Application\Command\EditDescription\EditDescriptionCommand(
            taskId: $id,
            description: $request->request->get('description', ''),
        ));

        $this->addFlash('success', 'Description updated');
        return $this->redirectToRoute('app_task_show', ['id' => $id]);
    }

    #[Route('/task/{id}/history', name: 'app_task_history')]
    public function history(
        string $id,
        TaskEventStore $eventStore,
        UserRepositoryInterface $userRepository,
        WorkflowRepositoryInterface $workflowRepository,
    ): Response {
        $events = $eventStore->findByTask($id);

        $userNames = [];
        $resolveUser = function (?string $userId) use ($userRepository, &$userNames): ?string {
            if ($userId === null) return null;
            if (!isset($userNames[$userId])) {
                $user = $userRepository->findById(new \App\IdentityAccess\Domain\Model\UserId($userId));
                $userNames[$userId] = $user ? $user->username() : $userId;
            }
            return $userNames[$userId];
        };

        $stageNames = [];
        $resolveStage = function (string $stageId) use ($workflowRepository, &$stageNames): string {
            if (!isset($stageNames[$stageId])) {
                $workflow = $workflowRepository->findDefault();
                $name = $stageId;
                if ($workflow !== null) {
                    foreach ($workflow->sortedStages() as $stage) {
                        if ($stage->id() === $stageId) {
                            $name = $stage->name();
                            break;
                        }
                    }
                }
                $stageNames[$stageId] = $name;
            }
            return $stageNames[$stageId];
        };

        foreach ($events as &$event) {
            if ($event['type'] === 'TaskAssigned') {
                $event['data']['newAssigneeName'] = $resolveUser($event['data']['newAssigneeId'] ?? null);
                $event['data']['oldAssigneeName'] = $resolveUser($event['data']['oldAssigneeId'] ?? null);
            }
            if ($event['type'] === 'TaskMoved') {
                $event['data']['fromStageName'] = $resolveStage($event['data']['fromStageId'] ?? '');
                $event['data']['toStageName'] = $resolveStage($event['data']['toStageId'] ?? '');
            }
        }
        unset($event);

        return $this->render('task/history.html.twig', [
            'events' => $events,
            'taskId' => $id,
        ]);
    }
}

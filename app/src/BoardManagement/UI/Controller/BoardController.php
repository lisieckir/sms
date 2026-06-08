<?php

declare(strict_types=1);

namespace App\BoardManagement\UI\Controller;

use App\BoardManagement\Application\Query\GetBoardHandler;
use App\BoardManagement\Application\Query\GetBoardQuery;
use App\BoardManagement\Domain\Model\WorkflowRepositoryInterface;
use App\TaskManagement\Application\Command\MoveTask\MoveTaskCommand;
use App\ClientManagement\Application\Query\ListClientsHandler;
use App\ClientManagement\Application\Query\ListClientsQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class BoardController extends AbstractController
{
    #[Route('/', name: 'app_board')]
    public function index(
        GetBoardHandler $handler,
        ListClientsHandler $clientHandler,
        WorkflowRepositoryInterface $workflowRepository,
        Request $request,
    ): Response {
        $clientId = $request->query->get('clientId');
        $assigneeId = $request->query->get('assigneeId');

        $stages = $handler(new GetBoardQuery(
            clientId: $clientId ?: null,
            assigneeId: $assigneeId ?: null,
        ));
        $clients = $clientHandler(new ListClientsQuery());
        $workflow = $workflowRepository->findDefault();

        return $this->render('board/kanban.html.twig', [
            'stages' => $stages,
            'clients' => $clients,
            'selectedClientId' => $clientId,
            'selectedAssigneeId' => $assigneeId,
            'workflow' => $workflow,
        ]);
    }

    #[Route('/board/task/{taskId}/move', name: 'app_board_move', methods: ['PATCH'])]
    public function moveTask(
        string $taskId,
        Request $request,
        MessageBusInterface $commandBus,
        GetBoardHandler $handler,
    ): Response {
        $stageId = $request->request->get('stageId');
        $position = (int) $request->request->get('position', 0);

        try {
            $commandBus->dispatch(new MoveTaskCommand(
                taskId: $taskId,
                stageId: $stageId,
                position: $position,
            ));

            $clientId = $request->query->get('clientId');
            $stages = $handler(new GetBoardQuery(
                clientId: $clientId ?: null,
            ));

            foreach ($stages as $stage) {
                if ($stage->id() === $stageId) {
                    return $this->render('board/_column.html.twig', [
                        'stage' => $stage,
                    ]);
                }
            }

            return new Response('', 200);
        } catch (\InvalidArgumentException $e) {
            return new Response($e->getMessage(), 422);
        }
    }

    #[Route('/board/column/{stageId}', name: 'app_board_column', methods: ['GET'])]
    public function column(
        string $stageId,
        GetBoardHandler $handler,
        Request $request,
    ): Response {
        $clientId = $request->query->get('clientId');
        $stages = $handler(new GetBoardQuery(clientId: $clientId ?: null));

        foreach ($stages as $stage) {
            if ($stage->id() === $stageId) {
                return $this->render('board/_column.html.twig', [
                    'stage' => $stage,
                ]);
            }
        }

        return new Response('', 404);
    }
}

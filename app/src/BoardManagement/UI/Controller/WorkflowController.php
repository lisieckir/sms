<?php

declare(strict_types=1);

namespace App\BoardManagement\UI\Controller;

use App\BoardManagement\Application\Command\AddStage\AddStageCommand;
use App\BoardManagement\Application\Command\AddStage\AddStageHandler;
use App\BoardManagement\Application\Command\AddTransition\AddTransitionCommand;
use App\BoardManagement\Application\Command\AddTransition\AddTransitionHandler;
use App\BoardManagement\Application\Command\CreateWorkflow\CreateWorkflowCommand;
use App\BoardManagement\Application\Command\RemoveStage\RemoveStageCommand;
use App\BoardManagement\Application\Command\RemoveStage\RemoveStageHandler;
use App\BoardManagement\Application\Command\RemoveTransition\RemoveTransitionCommand;
use App\BoardManagement\Application\Command\RemoveTransition\RemoveTransitionHandler;
use App\BoardManagement\Application\Command\ReorderStage\ReorderStageCommand;
use App\BoardManagement\Application\Command\ReorderStage\ReorderStageHandler;
use App\BoardManagement\Application\Query\GetWorkflowHandler;
use App\BoardManagement\Application\Query\GetWorkflowQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/workflow')]
class WorkflowController extends AbstractController
{
    #[Route('', name: 'app_workflow_edit')]
    public function edit(
        Request $request,
        GetWorkflowHandler $getWorkflow,
        AddStageHandler $addStage,
        RemoveStageHandler $removeStage,
        AddTransitionHandler $addTransition,
        RemoveTransitionHandler $removeTransition,
        ReorderStageHandler $reorderStage,
    ): Response {
        $workflow = $getWorkflow(new GetWorkflowQuery());

        if ($workflow === null) {
            return $this->redirectToRoute('app_workflow_init');
        }

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');

            try {
                match ($action) {
                    'add_stage' => $addStage(new AddStageCommand(
                        workflowId: $workflow['id'],
                        name: $request->request->get('name'),
                        position: (int) $request->request->get('position', 0),
                    )),
                    'remove_stage' => $removeStage(new RemoveStageCommand(
                        workflowId: $workflow['id'],
                        stageId: $request->request->get('stageId'),
                    )),
                    'add_transition' => $addTransition(new AddTransitionCommand(
                        workflowId: $workflow['id'],
                        fromStageId: $request->request->get('fromStageId'),
                        toStageId: $request->request->get('toStageId'),
                    )),
                    'remove_transition' => $removeTransition(new RemoveTransitionCommand(
                        workflowId: $workflow['id'],
                        fromStageId: $request->request->get('fromStageId'),
                        toStageId: $request->request->get('toStageId'),
                    )),
                    'reorder_stage' => $reorderStage(new ReorderStageCommand(
                        workflowId: $workflow['id'],
                        stageId: $request->request->get('stageId'),
                        newPosition: (int) $request->request->get('newPosition'),
                    )),
                    default => throw new \InvalidArgumentException('Unknown action'),
                };

                $this->addFlash('success', 'Workflow updated');
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            }

            return $this->redirectToRoute('app_workflow_edit');
        }

        return $this->render('board/workflow.html.twig', [
            'workflow' => $workflow,
        ]);
    }

    #[Route('/init', name: 'app_workflow_init')]
    public function init(): Response
    {
        return $this->render('board/workflow_init.html.twig');
    }

    #[Route('/create-default', name: 'app_workflow_create_default')]
    public function createDefault(): Response
    {
        $command = new CreateWorkflowCommand('Default Workflow', true);
        $this->dispatchMessage($command);

        $this->addFlash('success', 'Default workflow created');
        return $this->redirectToRoute('app_workflow_edit');
    }
}

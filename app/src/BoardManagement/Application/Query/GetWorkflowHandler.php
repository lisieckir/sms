<?php

declare(strict_types=1);

namespace App\BoardManagement\Application\Query;

use App\BoardManagement\Application\DTO\StageDTO;
use App\BoardManagement\Application\DTO\TransitionDTO;
use App\BoardManagement\Domain\Model\WorkflowRepositoryInterface;

final class GetWorkflowHandler
{
    public function __construct(
        private WorkflowRepositoryInterface $workflowRepository,
    ) {}

    public function __invoke(GetWorkflowQuery $query): ?array
    {
        $workflow = $this->workflowRepository->findDefault();
        if ($workflow === null) {
            return null;
        }

        return [
            'id' => $workflow->id()->value(),
            'name' => $workflow->name(),
            'isDefault' => $workflow->isDefault(),
            'stages' => array_map(
                fn($stage) => StageDTO::fromArray([
                    'id' => $stage->id(),
                    'name' => $stage->name(),
                    'position' => $stage->position(),
                ]),
                $workflow->sortedStages(),
            ),
            'transitions' => array_map(
                fn($transition) => TransitionDTO::fromArray([
                    'id' => $transition->id(),
                    'fromStageId' => $transition->fromStageId(),
                    'toStageId' => $transition->toStageId(),
                ]),
                $workflow->transitions(),
            ),
        ];
    }
}

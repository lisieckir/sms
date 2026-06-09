<?php

declare(strict_types=1);

namespace App\BoardManagement\Infrastructure\PocketBase;

use App\BoardManagement\Domain\Model\Stage;
use App\BoardManagement\Domain\Model\Transition;
use App\BoardManagement\Domain\Model\Workflow;
use App\BoardManagement\Domain\Model\WorkflowId;
use App\BoardManagement\Domain\Model\WorkflowRepositoryInterface;
use App\Core\Infrastructure\PocketBase\PocketBaseClient;

final readonly class WorkflowRepository implements WorkflowRepositoryInterface
{
    private const COLLECTION = 'workflows';

    public function __construct(
        private PocketBaseClient $pb,
    ) {}

    public function save(Workflow $workflow): void
    {
        $data = [
            'workflowId' => $workflow->id()->value(),
            'name' => $workflow->name(),
            'isDefault' => $workflow->isDefault(),
            'stages' => array_map(fn(Stage $s) => [
                'id' => $s->id(),
                'name' => $s->name(),
                'position' => $s->position(),
            ], $workflow->stages()),
            'transitions' => array_map(fn(Transition $t) => [
                'id' => $t->id(),
                'fromStageId' => $t->fromStageId(),
                'toStageId' => $t->toStageId(),
            ], $workflow->transitions()),
            'created' => $workflow->createdAt()->format('c'),
            'updated' => $workflow->updatedAt()->format('c'),
        ];

        $this->pb->upsert(self::COLLECTION, sprintf('workflowId="%s"', $workflow->id()->value()), $data);
    }

    public function findById(WorkflowId $id): ?Workflow
    {
        $record = $this->pb->first(self::COLLECTION, sprintf('workflowId="%s"', $id->value()));
        return $record !== null ? $this->toDomain($record) : null;
    }

    public function findDefault(): ?Workflow
    {
        $record = $this->pb->first(self::COLLECTION, 'isDefault=true');
        return $record !== null ? $this->toDomain($record) : null;
    }

    public function findAll(): array
    {
        $result = $this->pb->list(self::COLLECTION);
        return array_map(fn(array $r) => $this->toDomain($r), $result['items']);
    }

    public function nextIdentity(): WorkflowId
    {
        return WorkflowId::generate();
    }

    private function toDomain(array $record): Workflow
    {
        $fields = $record;

        $workflow = new \ReflectionClass(Workflow::class);
        $instance = $workflow->newInstanceWithoutConstructor();

        $idProp = $workflow->getProperty('id');
        $idProp->setAccessible(true);
        $idProp->setValue($instance, new WorkflowId($fields['workflowId']));

        $nameProp = $workflow->getProperty('name');
        $nameProp->setAccessible(true);
        $nameProp->setValue($instance, $fields['name']);

        $stagesProp = $workflow->getProperty('stages');
        $stagesProp->setAccessible(true);
        $stagesProp->setValue($instance, array_map(
            fn(array $s) => new Stage($s['id'], $s['name'], $s['position']),
            $fields['stages'] ?? [],
        ));

        $transitionsProp = $workflow->getProperty('transitions');
        $transitionsProp->setAccessible(true);
        $transitionsProp->setValue($instance, array_map(
            fn(array $t) => new Transition($t['id'], $t['fromStageId'], $t['toStageId']),
            $fields['transitions'] ?? [],
        ));

        $defaultProp = $workflow->getProperty('isDefault');
        $defaultProp->setAccessible(true);
        $defaultProp->setValue($instance, (bool) ($fields['isDefault'] ?? false));

        $createdProp = $workflow->getProperty('createdAt');
        $createdProp->setAccessible(true);
        $createdProp->setValue($instance, new \DateTimeImmutable($fields['created']));

        $updatedProp = $workflow->getProperty('updatedAt');
        $updatedProp->setAccessible(true);
        $updatedProp->setValue($instance, new \DateTimeImmutable($fields['updated']));

        return $instance;
    }
}

<?php

declare(strict_types=1);

namespace App\BoardManagement\Infrastructure\Doctrine;

use App\BoardManagement\Domain\Model\Stage;
use App\BoardManagement\Domain\Model\Transition;
use App\BoardManagement\Domain\Model\Workflow;
use App\BoardManagement\Domain\Model\WorkflowId;
use App\BoardManagement\Domain\Model\WorkflowRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

class WorkflowRepository implements WorkflowRepositoryInterface
{
    private DocumentRepository $repository;

    public function __construct(
        private DocumentManager $dm,
    ) {
        $this->repository = $dm->getRepository(WorkflowDocument::class);
    }

    public function save(Workflow $workflow): void
    {
        $doc = $this->repository->findOneBy(['workflowId' => $workflow->id()->value()]);
        if ($doc === null) {
            $doc = new WorkflowDocument();
            $doc->workflowId = $workflow->id()->value();
            $doc->createdAt = \DateTime::createFromImmutable($workflow->createdAt());
            $this->dm->persist($doc);
        }

        $doc->name = $workflow->name();
        $doc->isDefault = $workflow->isDefault();
        $doc->stages = array_map(fn(Stage $s) => [
            'id' => $s->id(),
            'name' => $s->name(),
            'position' => $s->position(),
        ], $workflow->stages());
        $doc->transitions = array_map(fn(Transition $t) => [
            'id' => $t->id(),
            'fromStageId' => $t->fromStageId(),
            'toStageId' => $t->toStageId(),
        ], $workflow->transitions());
        $doc->updatedAt = \DateTime::createFromImmutable($workflow->updatedAt());

        $this->dm->flush();
    }

    public function findById(WorkflowId $id): ?Workflow
    {
        $doc = $this->repository->findOneBy(['workflowId' => $id->value()]);
        return $doc ? $this->toDomain($doc) : null;
    }

    public function findDefault(): ?Workflow
    {
        $doc = $this->repository->findOneBy(['isDefault' => true]);
        return $doc ? $this->toDomain($doc) : null;
    }

    public function findAll(): array
    {
        $docs = $this->repository->findAll();
        return array_map(fn(WorkflowDocument $doc) => $this->toDomain($doc), $docs);
    }

    public function nextIdentity(): WorkflowId
    {
        return WorkflowId::generate();
    }

    private function toDomain(WorkflowDocument $doc): Workflow
    {
        $workflow = new \ReflectionClass(Workflow::class);
        $instance = $workflow->newInstanceWithoutConstructor();

        $idProp = $workflow->getProperty('id');
        $idProp->setAccessible(true);
        $idProp->setValue($instance, new WorkflowId($doc->workflowId));

        $nameProp = $workflow->getProperty('name');
        $nameProp->setAccessible(true);
        $nameProp->setValue($instance, $doc->name);

        $stagesProp = $workflow->getProperty('stages');
        $stagesProp->setAccessible(true);
        $stagesProp->setValue($instance, array_map(
            fn(array $s) => new Stage($s['id'], $s['name'], $s['position']),
            $doc->stages,
        ));

        $transitionsProp = $workflow->getProperty('transitions');
        $transitionsProp->setAccessible(true);
        $transitionsProp->setValue($instance, array_map(
            fn(array $t) => new Transition($t['id'], $t['fromStageId'], $t['toStageId']),
            $doc->transitions,
        ));

        $defaultProp = $workflow->getProperty('isDefault');
        $defaultProp->setAccessible(true);
        $defaultProp->setValue($instance, $doc->isDefault);

        $createdProp = $workflow->getProperty('createdAt');
        $createdProp->setAccessible(true);
        $createdProp->setValue($instance, \DateTimeImmutable::createFromMutable($doc->createdAt));

        $updatedProp = $workflow->getProperty('updatedAt');
        $updatedProp->setAccessible(true);
        $updatedProp->setValue($instance, \DateTimeImmutable::createFromMutable($doc->updatedAt));

        return $instance;
    }
}

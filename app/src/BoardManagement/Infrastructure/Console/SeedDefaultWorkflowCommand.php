<?php

declare(strict_types=1);

namespace App\BoardManagement\Infrastructure\Console;

use App\BoardManagement\Domain\Model\Workflow;
use App\BoardManagement\Domain\Model\WorkflowRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:seed-default-workflow', description: 'Seeds the default Kanban workflow with stages and transitions')]
final class SeedDefaultWorkflowCommand extends Command
{
    public function __construct(
        private WorkflowRepositoryInterface $workflowRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $existing = $this->workflowRepository->findDefault();
        if ($existing !== null) {
            $output->writeln('Default workflow already exists. Skipping.');
            return Command::SUCCESS;
        }

        $id = $this->workflowRepository->nextIdentity();
        $workflow = Workflow::createDefault($id);
        $this->workflowRepository->save($workflow);

        $output->writeln('Default workflow created with stages: To Do, In Progress, Review, Done');
        return Command::SUCCESS;
    }
}

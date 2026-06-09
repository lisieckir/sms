<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\DataFixtures;

use App\BoardManagement\Infrastructure\DataFixtures\WorkflowFixtures;
use App\ClientManagement\Infrastructure\DataFixtures\ClientFixtures;
use App\IdentityAccess\Infrastructure\DataFixtures\UserFixtures;
use App\TaskManagement\Infrastructure\DataFixtures\TaskFixtures;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:fixtures:load', description: 'Load development fixtures into PocketBase')]
final class LoadFixturesCommand extends Command
{
    public function __construct(
        private UserFixtures $userFixtures,
        private WorkflowFixtures $workflowFixtures,
        private ClientFixtures $clientFixtures,
        private TaskFixtures $taskFixtures,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->section('Loading UserFixtures');
        $this->userFixtures->load();

        $io->section('Loading WorkflowFixtures');
        $this->workflowFixtures->load();

        $io->section('Loading ClientFixtures');
        $this->clientFixtures->load();

        $io->section('Loading TaskFixtures');
        $this->taskFixtures->load();

        $io->success('Fixtures loaded successfully');

        return Command::SUCCESS;
    }
}

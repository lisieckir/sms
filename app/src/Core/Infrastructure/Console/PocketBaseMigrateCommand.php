<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Console;

use App\Core\Infrastructure\PocketBase\Migration\MigrationManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(name: 'app:pocketbase:migrate', description: 'Run pending PocketBase schema migrations')]
final class PocketBaseMigrateCommand extends Command
{
    private string $baseUrl;

    public function __construct(
        private HttpClientInterface $httpClient,
        string $pocketbaseUrl,
        private string $migrationDir,
    ) {
        $this->baseUrl = rtrim($pocketbaseUrl, '/') . '/api';
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('email', null, null, 'PocketBase admin email');
        $this->addOption('password', null, null, 'PocketBase admin password');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $adminEmail = $input->getOption('email')
            ?? $_SERVER['POCKETBASE_ADMIN_EMAIL']
            ?? $_ENV['POCKETBASE_ADMIN_EMAIL']
            ?? $io->ask('PocketBase admin email', 'admin@sidegigs.local');

        $adminPassword = $input->getOption('password')
            ?? $_SERVER['POCKETBASE_ADMIN_PASSWORD']
            ?? $_ENV['POCKETBASE_ADMIN_PASSWORD']
            ?? $io->askHidden('PocketBase admin password');

        $io->section('Authenticating to PocketBase');

        try {
            $response = $this->httpClient->request('POST', $this->baseUrl . '/collections/_superusers/auth-with-password', [
                'json' => [
                    'identity' => $adminEmail,
                    'password' => $adminPassword,
                ],
            ]);
            $data = $response->toArray();
            $token = $data['token'] ?? null;
        } catch (\Throwable $e) {
            $io->error('Authentication failed: ' . $e->getMessage());
            return Command::FAILURE;
        }

        if (!$token) {
            $io->error('Failed to obtain auth token');
            return Command::FAILURE;
        }

        $io->success('Authenticated successfully');

        $manager = new MigrationManager(
            $this->httpClient,
            $this->baseUrl,
            $token,
            $this->migrationDir,
        );

        $io->section('Running pending migrations');

        try {
            $executed = $manager->migrate();
        } catch (\Throwable $e) {
            $io->error('Migration failed: ' . $e->getMessage());
            return Command::FAILURE;
        }

        if ($executed === []) {
            $io->info('Nothing to migrate — all migrations already applied');
        } else {
            $io->success(sprintf('Executed %d migration(s): %s', count($executed), implode(', ', $executed)));
        }

        return Command::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Console;

use App\Core\Infrastructure\PocketBase\Migration\MigrationManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(name: 'app:pocketbase:migrate', description: 'Run pending PocketBase schema migrations')]
final class PocketBaseMigrateCommand extends Command
{
    private string $baseUrl;
    private string $rawUrl;

    public function __construct(
        private HttpClientInterface $httpClient,
        string $pocketbaseUrl,
        private string $migrationDir,
    ) {
        $this->rawUrl = rtrim($pocketbaseUrl, '/');
        $this->baseUrl = $this->rawUrl . '/api';
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('email', null, InputOption::VALUE_REQUIRED, 'PocketBase admin email');
        $this->addOption('password', null, InputOption::VALUE_REQUIRED, 'PocketBase admin password');
        $this->addOption('token', null, InputOption::VALUE_REQUIRED, 'PocketBase admin token (skips password auth)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $token = $input->getOption('token')
            ?? $_SERVER['POCKETBASE_ADMIN_TOKEN']
            ?? $_ENV['POCKETBASE_ADMIN_TOKEN']
            ?? null;

        if ($token) {
            $io->info('Using provided admin token');
        } else {
            $adminEmail = $input->getOption('email')
                ?? $_SERVER['POCKETBASE_ADMIN_EMAIL']
                ?? $_ENV['POCKETBASE_ADMIN_EMAIL']
                ?? ($input->isInteractive() ? $io->ask('PocketBase admin email', 'admin@sidegigs.local') : null);

            $adminPassword = $input->getOption('password')
                ?? $_SERVER['POCKETBASE_ADMIN_PASSWORD']
                ?? $_ENV['POCKETBASE_ADMIN_PASSWORD']
                ?? ($input->isInteractive() ? $io->askHidden('PocketBase admin password') : null);

            if (!$adminEmail || !$adminPassword) {
                $io->error('Set POCKETBASE_ADMIN_TOKEN, or provide POCKETBASE_ADMIN_EMAIL + POCKETBASE_ADMIN_PASSWORD');
                return Command::FAILURE;
            }

            $io->section('Authenticating to PocketBase');

            $token = $this->authenticate($adminEmail, $adminPassword);

            if ($token === null) {
                $io->error(sprintf(
                    'Authentication failed at %s. Check POCKETBASE_URL and admin credentials, or use POCKETBASE_ADMIN_TOKEN.',
                    $this->baseUrl
                ));
                return Command::FAILURE;
            }

            $io->success('Authenticated as ' . $adminEmail);
        }

        $manager = new MigrationManager(
            $this->httpClient,
            $this->rawUrl,
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

    private function authenticate(string $email, string $password): ?string
    {
        $endpoints = [
            '/collections/_superusers/auth-with-password',
            '/admins/auth-with-password',
        ];

        foreach ($endpoints as $endpoint) {
            try {
                $response = $this->httpClient->request('POST', $this->baseUrl . $endpoint, [
                    'json' => ['identity' => $email, 'password' => $password],
                    'headers' => ['Content-Type' => 'application/json'],
                ]);
                $data = $response->toArray();
                if (isset($data['token'])) {
                    return $data['token'];
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }
}

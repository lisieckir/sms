<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\PocketBase\Migration;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class MigrationManager
{
    private const MIGRATIONS_COLLECTION = 'app_migrations';

    private string $baseUrl;

    public function __construct(
        private HttpClientInterface $httpClient,
        string $pocketbaseUrl,
        private string $adminToken,
        private string $migrationDir,
    ) {
        $this->baseUrl = rtrim($pocketbaseUrl, '/') . '/api';
    }

    public function migrate(): array
    {
        $this->ensureMigrationsCollection();

        $applied = $this->getAppliedVersions();
        $pending = $this->discoverMigrations();

        $executed = [];

        foreach ($pending as $version => $migration) {
            if (isset($applied[$version])) {
                continue;
            }

            $migration->up();

            $this->recordMigration($version, $migration->getDescription());
            $executed[] = $version;
        }

        return $executed;
    }

    private function ensureMigrationsCollection(): void
    {
        try {
            $this->httpClient->request('GET', $this->baseUrl . '/collections/' . self::MIGRATIONS_COLLECTION . '/records', [
                'headers' => ['Authorization' => 'Bearer ' . $this->adminToken],
                'query' => ['perPage' => 1],
            ]);
        } catch (\Throwable) {
            $collections = $this->request('GET', '/collections');
            $exists = false;
            foreach ($collections['items'] ?? [] as $col) {
                if (($col['name'] ?? '') === self::MIGRATIONS_COLLECTION) {
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                $timestampFields = [
                    ['name' => 'created', 'type' => 'autodate', 'onCreate' => true],
                    ['name' => 'updated', 'type' => 'autodate', 'onCreate' => true, 'onUpdate' => true],
                ];
                $fields = [
                    ['name' => 'version', 'type' => 'text', 'required' => true, 'unique' => true],
                    ['name' => 'description', 'type' => 'text'],
                    ['name' => 'executedAt', 'type' => 'text'],
                ];
                $result = $this->request('POST', '/collections', [
                    'name' => self::MIGRATIONS_COLLECTION,
                    'type' => 'base',
                    'fields' => array_merge($fields, $timestampFields),
                ]);
                $colId = $result['id'] ?? null;
                if ($colId) {
                    try {
                        $this->request('PATCH', '/collections/' . $colId, [
                            'listRule' => '',
                            'viewRule' => '',
                            'createRule' => '',
                            'updateRule' => '',
                            'deleteRule' => '',
                        ]);
                    } catch (\Throwable) {
                    }
                }
            }
        }
    }

    private function getAppliedVersions(): array
    {
        try {
            $records = $this->request('GET', '/collections/' . self::MIGRATIONS_COLLECTION . '/records', [], [
                'perPage' => 200,
                'sort' => 'version',
            ]);
        } catch (\Throwable) {
            return [];
        }

        $applied = [];
        foreach ($records['items'] ?? [] as $item) {
            $applied[$item['version']] = $item;
        }
        return $applied;
    }

    private function discoverMigrations(): array
    {
        $files = glob($this->migrationDir . '/Version*.php');
        if ($files === false) {
            return [];
        }

        sort($files);

        $migrations = [];
        foreach ($files as $file) {
            require_once $file;
            $basename = pathinfo($file, PATHINFO_FILENAME);
            $className = $this->findMigrationClass($basename);
            if ($className === null) {
                continue;
            }
            $migration = new $className($this->httpClient, rtrim($this->baseUrl, '/api'), $this->adminToken);
            $migrations[$migration->getVersion()] = $migration;
        }

        ksort($migrations);
        return $migrations;
    }

    private function findMigrationClass(string $basename): ?string
    {
        $namespaces = [
            'App\\Core\\Infrastructure\\PocketBase\\Migration\\',
        ];
        foreach ($namespaces as $ns) {
            $class = $ns . $basename;
            if (class_exists($class)) {
                return $class;
            }
        }
        return null;
    }

    private function recordMigration(string $version, string $description): void
    {
        $this->request('POST', '/collections/' . self::MIGRATIONS_COLLECTION . '/records', [
            'version' => $version,
            'description' => $description,
            'executedAt' => (new \DateTimeImmutable())->format('c'),
        ]);
    }

    private function request(string $method, string $path, array $body = [], array $query = []): array
    {
        $options = [
            'headers' => ['Authorization' => 'Bearer ' . $this->adminToken],
        ];
        if ($body !== []) {
            $options['json'] = $body;
        }
        if ($query !== []) {
            $options['query'] = $query;
        }

        try {
            $response = $this->httpClient->request($method, $this->baseUrl . $path, $options);
            return $response->toArray();
        } catch (\Throwable $e) {
            try {
                $content = $e->getResponse()?->getContent(false) ?? 'no response body';
            } catch (\Throwable) {
                $content = 'could not read response';
            }
            throw new \RuntimeException(sprintf(
                '%s %s failed: %s — body: %s',
                $method, $this->baseUrl . $path, $e->getMessage(), $content
            ));
        }
    }
}

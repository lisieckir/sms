<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\PocketBase\Migration;

use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class AbstractMigration implements MigrationInterface
{
    private string $version;

    private string $baseUrl;

    public function __construct(
        private HttpClientInterface $httpClient,
        string $pocketbaseUrl,
        private string $adminToken,
    ) {
        $this->baseUrl = rtrim($pocketbaseUrl, '/') . '/api';
        $this->version = $this->resolveVersion();
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    protected function addField(string $collectionName, string $fieldName, string $fieldType, array $extra = []): void
    {
        $collection = $this->findCollection($collectionName);
        if ($collection === null) {
            throw new \RuntimeException("Collection '$collectionName' not found");
        }

        foreach ($collection['fields'] ?? [] as $f) {
            if (($f['name'] ?? '') === $fieldName) {
                return;
            }
        }

        $newField = array_merge(['name' => $fieldName, 'type' => $fieldType], $extra);
        $updatedFields = array_merge($collection['fields'] ?? [], [$newField]);

        $this->request('PATCH', '/collections/' . $collection['id'], [
            'fields' => $updatedFields,
        ]);
    }

    protected function removeField(string $collectionName, string $fieldName): void
    {
        $collection = $this->findCollection($collectionName);
        if ($collection === null) {
            throw new \RuntimeException("Collection '$collectionName' not found");
        }

        $updatedFields = array_values(array_filter($collection['fields'] ?? [], fn(array $f) => ($f['name'] ?? '') !== $fieldName));

        if (count($updatedFields) === count($collection['fields'] ?? [])) {
            return;
        }

        $this->request('PATCH', '/collections/' . $collection['id'], [
            'fields' => $updatedFields,
        ]);
    }

    protected function createRecord(string $collectionName, array $data): array
    {
        return $this->request('POST', '/collections/' . $collectionName . '/records', $data);
    }

    protected function updateRecord(string $collectionName, string $filter, array $data): void
    {
        $existing = $this->request('GET', '/collections/' . $collectionName . '/records', [], [
            'filter' => $filter,
            'perPage' => 200,
        ]);

        foreach ($existing['items'] ?? [] as $item) {
            $this->request('PATCH', '/collections/' . $collectionName . '/records/' . $item['id'], $data);
        }
    }

    protected function upsertRecord(string $collectionName, string $filter, array $data): array
    {
        $existing = $this->request('GET', '/collections/' . $collectionName . '/records', [], [
            'filter' => $filter,
            'perPage' => 1,
        ]);

        $item = $existing['items'][0] ?? null;
        if ($item !== null) {
            return $this->request('PATCH', '/collections/' . $collectionName . '/records/' . $item['id'], $data);
        }
        return $this->request('POST', '/collections/' . $collectionName . '/records', $data);
    }

    protected function deleteRecord(string $collectionName, string $filter): void
    {
        $existing = $this->request('GET', '/collections/' . $collectionName . '/records', [], [
            'filter' => $filter,
            'perPage' => 200,
        ]);

        foreach ($existing['items'] ?? [] as $item) {
            $this->request('DELETE', '/collections/' . $collectionName . '/records/' . $item['id']);
        }
    }

    protected function fetchRecords(string $collectionName, array $params = []): array
    {
        return $this->request('GET', '/collections/' . $collectionName . '/records', [], $params);
    }

    protected function api(string $method, string $path, array $body = [], array $query = []): array
    {
        return $this->request($method, $path, $body, $query);
    }

    private function findCollection(string $name): ?array
    {
        $collections = $this->request('GET', '/collections');
        foreach ($collections['items'] ?? [] as $col) {
            if (($col['name'] ?? '') === $name) {
                return $col;
            }
        }
        return null;
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

        $response = $this->httpClient->request($method, $this->baseUrl . $path, $options);
        return $response->toArray();
    }

    private function resolveVersion(): string
    {
        $ref = new \ReflectionClass($this);
        $name = $ref->getShortName();
        if (preg_match('/^Version(\d{14})/', $name, $m)) {
            return $m[1];
        }
        return $name;
    }
}

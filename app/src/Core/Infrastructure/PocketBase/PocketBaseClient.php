<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\PocketBase;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class PocketBaseClient
{
    private string $baseUrl;

    public function __construct(
        private HttpClientInterface $httpClient,
        string $pocketbaseUrl,
    ) {
        $this->baseUrl = rtrim($pocketbaseUrl, '/') . '/api';
    }

    public function list(string $collection, array $params = []): array
    {
        $url = $this->baseUrl . '/collections/' . $collection . '/records';
        if ($params !== []) {
            $url .= '?' . http_build_query($params);
        }
        $response = $this->httpClient->request('GET', $url);
        return $response->toArray();
    }

    public function first(string $collection, string $filter): ?array
    {
        $result = $this->list($collection, ['filter' => $filter, 'perPage' => 1]);
        return $result['items'][0] ?? null;
    }

    public function getById(string $collection, string $id): array
    {
        $url = $this->baseUrl . '/collections/' . $collection . '/records/' . $id;
        $response = $this->httpClient->request('GET', $url);
        return $response->toArray();
    }

    public function create(string $collection, array $data): array
    {
        $url = $this->baseUrl . '/collections/' . $collection . '/records';
        $response = $this->httpClient->request('POST', $url, [
            'json' => $data,
        ]);
        return $response->toArray();
    }

    public function update(string $collection, string $id, array $data): array
    {
        $url = $this->baseUrl . '/collections/' . $collection . '/records/' . $id;
        $response = $this->httpClient->request('PATCH', $url, [
            'json' => $data,
        ]);
        return $response->toArray();
    }

    public function upsert(string $collection, string $filter, array $data): array
    {
        $existing = $this->first($collection, $filter);
        if ($existing !== null) {
            return $this->update($collection, $existing['id'], $data);
        }
        return $this->create($collection, $data);
    }

    public function delete(string $collection, string $id): void
    {
        $url = $this->baseUrl . '/collections/' . $collection . '/records/' . $id;
        $this->httpClient->request('DELETE', $url);
    }
}

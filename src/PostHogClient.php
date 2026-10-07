<?php

namespace JeffersonGoncalves\MetricsPostHog;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\MetricsPostHog\Exceptions\AuthenticationException;
use JeffersonGoncalves\MetricsPostHog\Exceptions\PostHogException;
use JeffersonGoncalves\MetricsPostHog\Exceptions\RateLimitException;

class PostHogClient
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $projectId,
        private readonly string $host = 'https://us.posthog.com',
    ) {}

    /**
     * Run a HogQL query through the project query endpoint.
     *
     * @return array<int|string, mixed> {results: list<list<mixed>>, columns: list<string>, ...}
     */
    public function hogql(string $query): array
    {
        if ($this->apiKey === '') {
            throw AuthenticationException::missingApiKey();
        }

        if ($this->projectId === '') {
            throw AuthenticationException::missingProjectId();
        }

        $response = Http::withToken($this->apiKey)
            ->acceptJson()
            ->asJson()
            ->post(rtrim($this->host, '/').'/api/projects/'.rawurlencode($this->projectId).'/query/', [
                'query' => ['kind' => 'HogQLQuery', 'query' => $query],
            ]);

        return $this->handle($response);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function handle(Response $response): array
    {
        if ($response->status() === 401) {
            throw AuthenticationException::invalidApiKey();
        }

        if ($response->status() === 403) {
            throw AuthenticationException::forbidden();
        }

        if ($response->status() === 429) {
            throw RateLimitException::exceeded();
        }

        if (! $response->successful()) {
            throw PostHogException::fromResponse($response->status(), (string) ($response->json('detail') ?? $response->body()));
        }

        $data = $response->json();

        return is_array($data) ? $data : [];
    }
}

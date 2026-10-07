<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\MetricsPostHog\Exceptions\AuthenticationException;
use JeffersonGoncalves\MetricsPostHog\Exceptions\RateLimitException;
use JeffersonGoncalves\MetricsPostHog\Facades\PostHog;
use JeffersonGoncalves\MetricsPostHog\Settings\PostHogSettings;

const QUERY_URL = 'eu.posthog.com/api/projects/4242/query/';

/**
 * @param  list<string>  $columns
 * @param  list<list<mixed>>  $results
 */
function hogql(array $columns, array $results): array
{
    return ['columns' => $columns, 'results' => $results, 'types' => []];
}

it('combines pageview and session totals', function (): void {
    Http::fakeSequence(QUERY_URL)
        ->push(hogql(['visitors', 'pageviews'], [[120, 340]]))
        ->push(hogql(['visits', 'bounce_rate', 'visit_duration'], [[150, 41.5, 95.5]]));

    $stats = PostHog::aggregate(7);

    expect($stats->visitors())->toBe(120)
        ->and($stats->visits())->toBe(150)
        ->and($stats->pageviews())->toBe(340)
        ->and($stats->bounceRate())->toBe(41.5)
        ->and($stats->visitDuration())->toBe(95.5);

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer phx_test_key')
        && $request['query']['kind'] === 'HogQLQuery'
        && str_contains($request['query']['query'], 'FROM events')
        && str_contains($request['query']['query'], 'INTERVAL 7 DAY'));
    Http::assertSent(fn ($request) => str_contains($request['query']['query'], 'FROM sessions'));
});

it('returns a daily timeseries', function (): void {
    Http::fake([QUERY_URL => Http::response(hogql(['day', 'visitors', 'pageviews'], [
        ['2026-10-05', 10, 25],
        ['2026-10-06', 12, 30],
    ]))]);

    $rows = PostHog::timeseries(days: 7);

    expect(array_map(fn ($row) => $row->label, $rows))->toBe(['2026-10-05', '2026-10-06'])
        ->and($rows[1]->visitors())->toBe(12)
        ->and($rows[1]->pageviews())->toBe(30);

    Http::assertSent(fn ($request) => str_contains($request['query']['query'], 'INTERVAL 6 DAY'));
});

it('builds top-N breakdowns on event properties', function (): void {
    Http::fake([QUERY_URL => Http::response(hogql(['label', 'visitors', 'pageviews'], [
        ['/', 50, 80],
        ['/pricing', 20, 22],
    ]))]);

    $pages = PostHog::pages(limit: 10);

    expect($pages)->toHaveCount(2)
        ->and($pages[0]->label)->toBe('/')
        ->and($pages[0]->visitors())->toBe(50);

    Http::assertSent(fn ($request) => str_contains($request['query']['query'], 'properties.$pathname')
        && str_contains($request['query']['query'], 'LIMIT 10'));
});

it('rejects property names that are not identifiers', function (): void {
    PostHog::breakdown("x' OR 1=1 --");
})->throws(InvalidArgumentException::class);

it('reads realtime visitors', function (): void {
    Http::fake([QUERY_URL => Http::response(hogql(['visitors'], [[7]]))]);

    expect(PostHog::realtimeVisitors())->toBe(7);
});

it('explains an invalid key', function (): void {
    Http::fake([QUERY_URL => Http::response(['detail' => 'Invalid token'], 401)]);

    PostHog::aggregate();
})->throws(AuthenticationException::class, 'personal API key');

it('reports rate limits', function (): void {
    Http::fake([QUERY_URL => Http::response([], 429)]);

    PostHog::aggregate();
})->throws(RateLimitException::class);

it('stores the personal API key encrypted', function (): void {
    $payload = DB::table('settings')->where('name', 'personal_api_key')->value('payload');

    expect($payload)->not->toContain('phx_test_key')
        ->and(app(PostHogSettings::class)->personal_api_key)->toBe('phx_test_key');
});

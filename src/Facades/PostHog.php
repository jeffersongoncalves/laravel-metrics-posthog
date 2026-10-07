<?php

namespace JeffersonGoncalves\MetricsPostHog\Facades;

use Illuminate\Support\Facades\Facade;
use JeffersonGoncalves\MetricsPostHog\Data\AggregateStats;
use JeffersonGoncalves\MetricsPostHog\Data\StatsRow;

/**
 * @method static array<int|string, mixed> query(string $hogql)
 * @method static AggregateStats aggregate(int $days = 30)
 * @method static list<StatsRow> timeseries(int $days = 30)
 * @method static list<StatsRow> breakdown(string $property, int $days = 30, int $limit = 100)
 * @method static list<StatsRow> pages(int $days = 30, int $limit = 100)
 * @method static list<StatsRow> sources(int $days = 30, int $limit = 100)
 * @method static list<StatsRow> countries(int $days = 30, int $limit = 100)
 * @method static list<StatsRow> browsers(int $days = 30, int $limit = 100)
 * @method static list<StatsRow> devices(int $days = 30, int $limit = 100)
 * @method static int realtimeVisitors()
 *
 * @see \JeffersonGoncalves\MetricsPostHog\PostHog
 */
class PostHog extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'metrics-posthog';
    }
}

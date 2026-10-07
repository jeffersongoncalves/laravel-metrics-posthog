<?php

namespace JeffersonGoncalves\MetricsPostHog;

use InvalidArgumentException;
use JeffersonGoncalves\MetricsPostHog\Data\AggregateStats;
use JeffersonGoncalves\MetricsPostHog\Data\StatsRow;

/**
 * PostHog web analytics through HogQL, with the same shape as the other jeffersongoncalves metrics packages
 * (visitors, visits, pageviews, bounce_rate, visit_duration). Only `$pageview` events are counted as traffic.
 */
class PostHog
{
    public function __construct(
        private readonly PostHogClient $client,
    ) {}

    /**
     * Raw HogQL query.
     *
     * @return array<int|string, mixed>
     */
    public function query(string $hogql): array
    {
        return $this->client->hogql($hogql);
    }

    /**
     * Totals over the last $days days.
     */
    public function aggregate(int $days = 30): AggregateStats
    {
        $days = self::positive($days);

        $traffic = StatsRow::firstFromResponse($this->query(
            "SELECT count(DISTINCT person_id) AS visitors, count() AS pageviews FROM events WHERE event = '\$pageview' AND timestamp >= now() - INTERVAL {$days} DAY"
        ));

        $sessions = StatsRow::firstFromResponse($this->query(
            "SELECT count() AS visits, countIf(\$is_bounce) * 100 / greatest(count(), 1) AS bounce_rate, ifNull(avg(\$session_duration), 0) AS visit_duration FROM sessions WHERE \$start_timestamp >= now() - INTERVAL {$days} DAY"
        ));

        return new AggregateStats($traffic + $sessions);
    }

    /**
     * Daily buckets over the last $days days (including today); labels are Y-m-d.
     *
     * @return list<StatsRow>
     */
    public function timeseries(int $days = 30): array
    {
        $since = self::positive($days) - 1;

        return StatsRow::collectionFromResponse($this->query(
            "SELECT toDate(timestamp) AS day, count(DISTINCT person_id) AS visitors, count() AS pageviews FROM events WHERE event = '\$pageview' AND timestamp >= toStartOfDay(now()) - INTERVAL {$since} DAY GROUP BY day ORDER BY day"
        ));
    }

    /**
     * Top values of an event property (e.g. `$pathname`, `$referring_domain`), ordered by visitors.
     *
     * @return list<StatsRow>
     */
    public function breakdown(string $property, int $days = 30, int $limit = 100): array
    {
        if (preg_match('/^\$?[A-Za-z0-9_]+$/', $property) !== 1) {
            throw new InvalidArgumentException("Invalid PostHog property name [{$property}].");
        }

        $days = self::positive($days);
        $limit = self::positive($limit);

        return StatsRow::collectionFromResponse($this->query(
            "SELECT properties.{$property} AS label, count(DISTINCT person_id) AS visitors, count() AS pageviews FROM events WHERE event = '\$pageview' AND timestamp >= now() - INTERVAL {$days} DAY GROUP BY label ORDER BY visitors DESC LIMIT {$limit}"
        ));
    }

    /** @return list<StatsRow> */
    public function pages(int $days = 30, int $limit = 100): array
    {
        return $this->breakdown('$pathname', $days, $limit);
    }

    /** @return list<StatsRow> */
    public function sources(int $days = 30, int $limit = 100): array
    {
        return $this->breakdown('$referring_domain', $days, $limit);
    }

    /** @return list<StatsRow> */
    public function countries(int $days = 30, int $limit = 100): array
    {
        return $this->breakdown('$geoip_country_name', $days, $limit);
    }

    /** @return list<StatsRow> */
    public function browsers(int $days = 30, int $limit = 100): array
    {
        return $this->breakdown('$browser', $days, $limit);
    }

    /** @return list<StatsRow> */
    public function devices(int $days = 30, int $limit = 100): array
    {
        return $this->breakdown('$device_type', $days, $limit);
    }

    /**
     * Distinct people with any event in the last 5 minutes.
     */
    public function realtimeVisitors(): int
    {
        $row = StatsRow::firstFromResponse($this->query(
            'SELECT count(DISTINCT person_id) AS visitors FROM events WHERE timestamp >= now() - INTERVAL 5 MINUTE'
        ));

        return (int) ($row['visitors'] ?? 0);
    }

    private static function positive(int $value): int
    {
        if ($value < 1) {
            throw new InvalidArgumentException('Expected a positive number of days/rows.');
        }

        return $value;
    }
}

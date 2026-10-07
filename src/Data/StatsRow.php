<?php

namespace JeffersonGoncalves\MetricsPostHog\Data;

class StatsRow
{
    /**
     * @param  array<string, int|float|null>  $metrics
     */
    public function __construct(
        public readonly string $label,
        public readonly array $metrics = [],
    ) {}

    /**
     * Rows of a HogQL response: the first column is the label, the remaining named columns are metrics.
     *
     * @param  array<int|string, mixed>  $response
     * @return list<self>
     */
    public static function collectionFromResponse(array $response): array
    {
        $columns = self::columns($response);
        $rows = [];

        foreach (is_array($response['results'] ?? null) ? $response['results'] : [] as $result) {
            if (! is_array($result)) {
                continue;
            }

            $values = array_values($result);
            $metrics = [];

            foreach (array_slice($columns, 1, null, true) as $index => $name) {
                $metrics[$name] = self::cast($values[$index] ?? null);
            }

            $rows[] = new self(label: (string) ($values[0] ?? ''), metrics: $metrics);
        }

        return $rows;
    }

    /**
     * Named columns of the first row of a single-row (aggregate) HogQL response.
     *
     * @param  array<int|string, mixed>  $response
     * @return array<string, int|float|null>
     */
    public static function firstFromResponse(array $response): array
    {
        $columns = self::columns($response);
        $first = is_array($response['results'][0] ?? null) ? array_values($response['results'][0]) : [];

        $metrics = [];

        foreach ($columns as $index => $name) {
            $metrics[$name] = self::cast($first[$index] ?? null);
        }

        return $metrics;
    }

    public function metric(string $name, int|float|null $default = null): int|float|null
    {
        return $this->metrics[$name] ?? $default;
    }

    public function visitors(): int
    {
        return (int) $this->metric('visitors', 0);
    }

    public function pageviews(): int
    {
        return (int) $this->metric('pageviews', 0);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['label' => $this->label, 'metrics' => $this->metrics];
    }

    public static function cast(mixed $value): int|float|null
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return str_contains((string) $value, '.') ? (float) $value : (int) $value;
        }

        return null;
    }

    /**
     * @param  array<int|string, mixed>  $response
     * @return list<string>
     */
    private static function columns(array $response): array
    {
        return array_values(array_map(
            fn (mixed $column): string => (string) $column,
            is_array($response['columns'] ?? null) ? $response['columns'] : [],
        ));
    }
}

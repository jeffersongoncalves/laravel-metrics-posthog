<?php

namespace JeffersonGoncalves\MetricsPostHog\Settings;

use Spatie\LaravelSettings\Settings;

class PostHogSettings extends Settings
{
    /** Personal API key (phx_...) with the query:read scope. Stored encrypted. */
    public string $personal_api_key;

    /** Numeric project id (Project settings > Project ID). */
    public string $project_id;

    /** https://us.posthog.com, https://eu.posthog.com or a self-hosted URL. */
    public string $host;

    public static function group(): string
    {
        return 'metrics-posthog';
    }

    /** @return list<string> */
    public static function encrypted(): array
    {
        return ['personal_api_key'];
    }
}

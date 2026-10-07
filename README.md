<div class="filament-hidden">

![Laravel Metrics PostHog](https://raw.githubusercontent.com/jeffersongoncalves/laravel-metrics-posthog/main/art/jeffersongoncalves-laravel-metrics-posthog.png)

</div>

# Laravel Metrics PostHog

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jeffersongoncalves/laravel-metrics-posthog.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-metrics-posthog)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/jeffersongoncalves/laravel-metrics-posthog/pint.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/jeffersongoncalves/laravel-metrics-posthog/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![PHPStan](https://img.shields.io/github/actions/workflow/status/jeffersongoncalves/laravel-metrics-posthog/phpstan.yml?branch=main&label=PHPStan&style=flat-square)](https://github.com/jeffersongoncalves/laravel-metrics-posthog/actions?query=workflow%3APHPStan+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/jeffersongoncalves/laravel-metrics-posthog.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-metrics-posthog)

Laravel package to read web analytics from [PostHog](https://posthog.com) with HogQL queries: visitors, sessions, pageviews, bounce rate, session duration, top pages, sources, countries, browsers, devices and realtime visitors — for dashboards, reports and automations.

Works with PostHog Cloud (US and EU) and self-hosted PostHog.

Settings are stored in the database via [spatie/laravel-settings](https://github.com/spatie/laravel-settings) — no config files needed. The personal API key is stored encrypted.

> Looking to add the PostHog tracking snippet to your Blade layout instead? Use [jeffersongoncalves/laravel-posthog](https://github.com/jeffersongoncalves/laravel-posthog).

## Installation

```bash
composer require jeffersongoncalves/laravel-metrics-posthog
```

Run migrations to create the settings:

```bash
php artisan migrate
```

## Configuration

After migration, the settings are seeded from environment variables:

```env
POSTHOG_PERSONAL_API_KEY=phx_...
POSTHOG_PROJECT_ID=12345
POSTHOG_HOST=https://us.posthog.com
```

Create a **personal API key** (not the project key used by the tracking snippet) under **Settings → Personal API keys**, with the `query:read` scope. The project id is in **Project settings**. Use `https://eu.posthog.com` for the EU cloud, or your own URL when self-hosting.

You can also update settings programmatically:

```php
use JeffersonGoncalves\MetricsPostHog\Settings\PostHogSettings;

$settings = app(PostHogSettings::class);
$settings->personal_api_key = 'phx_...';
$settings->project_id = '12345';
$settings->host = 'https://eu.posthog.com';
$settings->save();
```

## Usage

```php
use JeffersonGoncalves\MetricsPostHog\Facades\PostHog;
```

Traffic metrics count `$pageview` events; session metrics come from the `sessions` table. `$days` counts back from now.

### Aggregate totals

```php
$stats = PostHog::aggregate(days: 30);

$stats->visitors();       // 1234  distinct persons with a pageview
$stats->visits();         // 1500  sessions
$stats->pageviews();      // 4321  $pageview events
$stats->bounceRate();     // 41.5  percentage of bounced sessions
$stats->visitDuration();  // 96.0  average session duration, seconds
```

### Timeseries

```php
// Daily visitors and pageviews over the last 30 days (including today)
foreach (PostHog::timeseries() as $row) {
    echo $row->label.': '.$row->visitors();  // 2026-09-01: 120
}
```

### Breakdowns

```php
$pages = PostHog::pages(days: 30, limit: 10);   // $pathname
$sources = PostHog::sources();                   // $referring_domain
$countries = PostHog::countries();               // $geoip_country_name
$browsers = PostHog::browsers();                 // $browser
$devices = PostHog::devices();                   // $device_type

foreach ($pages as $row) {
    echo $row->label;                  // /blog/hello-world
    echo $row->visitors();             // 90
    echo $row->metric('pageviews');    // 120
}

// Any other event property
$rows = PostHog::breakdown('$os', days: 7);
$rows = PostHog::breakdown('plan');
```

### Realtime

```php
$visitors = PostHog::realtimeVisitors();  // distinct persons with any event in the last 5 minutes
```

### Raw HogQL

```php
$response = PostHog::query("SELECT event, count() FROM events WHERE timestamp >= now() - INTERVAL 1 DAY GROUP BY event");

$response['columns'];  // ['event', 'count()']
$response['results'];  // [['$pageview', 812], ...]
```

## Error Handling

| Exception | When |
|-----------|------|
| `AuthenticationException` | Missing API key or project id, 401 (invalid key / wrong host) or 403 (missing `query:read` scope) |
| `RateLimitException` | HTTP 429 |
| `PostHogException` | Any other API failure (base class of both above) |

## Testing

```bash
composer test
```

## Code Style

```bash
composer format
```

## Static Analysis

```bash
composer analyse
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Jefferson Gonçalves](https://github.com/jeffersongoncalves)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

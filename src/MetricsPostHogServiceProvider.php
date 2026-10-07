<?php

namespace JeffersonGoncalves\MetricsPostHog;

use Illuminate\Support\Facades\Config;
use JeffersonGoncalves\MetricsPostHog\Settings\PostHogSettings;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MetricsPostHogServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('metrics-posthog');
    }

    public function packageRegistered(): void
    {
        Config::set('settings.settings', array_merge(
            Config::get('settings.settings', []),
            [PostHogSettings::class]
        ));

        $this->app->singleton(PostHogClient::class, function () {
            $settings = app(PostHogSettings::class);

            return new PostHogClient(
                apiKey: $settings->personal_api_key,
                projectId: $settings->project_id,
                host: $settings->host,
            );
        });

        $this->app->singleton('metrics-posthog', fn ($app) => new PostHog($app->make(PostHogClient::class)));

        $this->app->alias('metrics-posthog', PostHog::class);
    }

    public function packageBooted(): void
    {
        $migrationsPath = __DIR__.'/../database/settings';

        Config::set('settings.migrations_paths', array_merge(
            [$migrationsPath],
            Config::get('settings.migrations_paths', [])
        ));

        $this->publishes([
            $migrationsPath => database_path('settings'),
        ], 'metrics-posthog-settings-migrations');
    }
}

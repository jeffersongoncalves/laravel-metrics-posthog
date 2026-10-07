<?php

use Spatie\LaravelSettings\Migrations\SettingsBlueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->inGroup('metrics-posthog', function (SettingsBlueprint $blueprint): void {
            $blueprint->addEncrypted('personal_api_key', env('POSTHOG_PERSONAL_API_KEY', ''));
            $blueprint->add('project_id', env('POSTHOG_PROJECT_ID', ''));
            $blueprint->add('host', env('POSTHOG_HOST', 'https://us.posthog.com'));
        });
    }
};

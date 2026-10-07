# Changelog

All notable changes to this project will be documented in this file.

## 1.0.0 - 2026-10-07

First release.

Laravel client for PostHog web analytics through the HogQL query API. Works with PostHog Cloud (US/EU) and self-hosted.

- `PostHog` facade: `aggregate`, `timeseries`, `breakdown`, `pages`, `sources`, `countries`, `browsers`, `devices`, `realtimeVisitors`, raw `query`
- Typed exceptions: `AuthenticationException`, `RateLimitException`, `PostHogException`
- Settings stored via spatie/laravel-settings (personal API key encrypted), seeded from `POSTHOG_PERSONAL_API_KEY`, `POSTHOG_PROJECT_ID` and `POSTHOG_HOST`

Requires PHP 8.2+ and Laravel 11, 12 or 13.

## [Unreleased]

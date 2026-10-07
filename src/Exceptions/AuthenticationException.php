<?php

namespace JeffersonGoncalves\MetricsPostHog\Exceptions;

class AuthenticationException extends PostHogException
{
    public static function missingApiKey(): self
    {
        return new self('PostHog personal API key is not configured.');
    }

    public static function missingProjectId(): self
    {
        return new self('PostHog project id is not configured.');
    }

    public static function invalidApiKey(): self
    {
        return new self('PostHog rejected the personal API key. Check the key and the host (US or EU cloud).', 401);
    }

    public static function forbidden(): self
    {
        return new self('The personal API key cannot query this project. It needs the "query:read" scope and access to the project.', 403);
    }
}

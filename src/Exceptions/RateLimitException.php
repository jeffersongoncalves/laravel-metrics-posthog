<?php

namespace JeffersonGoncalves\MetricsPostHog\Exceptions;

class RateLimitException extends PostHogException
{
    public static function exceeded(): self
    {
        return new self('PostHog query rate limit exceeded. Try again later.', 429);
    }
}

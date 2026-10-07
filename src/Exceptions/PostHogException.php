<?php

namespace JeffersonGoncalves\MetricsPostHog\Exceptions;

use Exception;

class PostHogException extends Exception
{
    public static function fromResponse(int $statusCode, string $message): self
    {
        return new self("PostHog API error ({$statusCode}): {$message}", $statusCode);
    }
}

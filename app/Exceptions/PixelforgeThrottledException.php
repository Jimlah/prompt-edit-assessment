<?php

namespace App\Exceptions;

use Prism\Prism\Exceptions\PrismException;

/**
 * Pixelforge throttled the request: per-minute rate limits, concurrency
 * limits, the daily quota, or a temporary overload (503). The caller should
 * wait for the given seconds and try again.
 */
final class PixelforgeThrottledException extends PrismException
{
    public function __construct(
        public readonly string $errorCode,
        public readonly string $errorMessage,
        public readonly int $retryAfter,
    ) {
        parent::__construct("Pixelforge throttled the request: [{$errorCode}] {$errorMessage}");
    }

    /**
     * Error codes for which retrying is pointless inside a single run
     * (the constraint will not lift by waiting a short while).
     */
    public function shouldGiveUp(): bool
    {
        return $this->errorCode === 'daily_limit_exceeded' || $this->retryAfter > 900;
    }
}

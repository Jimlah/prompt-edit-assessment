<?php

namespace App\Exceptions;

use Prism\Prism\Exceptions\PrismException;

/**
 * A Pixelforge error that will never resolve on retry: content policy
 * violations, invalid keys, validation errors, and generations that failed
 * for good.
 */
final class PixelforgePermanentException extends PrismException
{
    public function __construct(
        public readonly string $errorCode,
        public readonly string $errorMessage,
    ) {
        parent::__construct("Pixelforge rejected the request: [{$errorCode}] {$errorMessage}");
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies Pixelforge webhook signatures against the RAW request body, before
 * any JSON decoding, exactly as the provider documents it:
 *
 *   Pixelforge-Signature: t=<unix timestamp>,v1=<hex HMAC-SHA256>
 *
 * where the signature is HMAC-SHA256 of "<timestamp>.<raw body>" keyed with
 * the signing secret of the API key. Compared in constant time; old timestamps
 * are rejected so captured requests cannot be replayed.
 */
final class VerifyPixelforgeSignature
{
    /** A signed request is only accepted within this window of its timestamp. */
    private const TIMESTAMP_TOLERANCE_SECONDS = 300;

    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('prism.providers.pixelforge.webhook_secret');

        if ($secret === '') {
            abort(500, 'The Pixelforge webhook secret is not configured.');
        }

        $payload = $request->getContent();
        $signature = $this->parseSignature((string) $request->header('Pixelforge-Signature', ''));
        $timestamp = (int) ($signature['t'] ?? 0);
        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        if (! hash_equals($expected, (string) ($signature['v1'] ?? ''))
            || abs(now()->getTimestamp() - $timestamp) > self::TIMESTAMP_TOLERANCE_SECONDS) {
            abort(400, 'Invalid Pixelforge signature.');
        }

        return $next($request);
    }

    /**
     * The header is "t=<unix timestamp>,v1=<signature>".
     *
     * @return array<string, string>
     */
    private function parseSignature(string $header): array
    {
        $signature = [];

        foreach (explode(',', $header) as $part) {
            [$key, $value] = array_pad(explode('=', $part, 2), 2, '');
            $signature[trim($key)] = trim($value);
        }

        return $signature;
    }
}

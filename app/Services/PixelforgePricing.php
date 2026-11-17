<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Reads Pixelforge's published pricing (GET /v1/pricing) and caches it, so the
 * app never hardcodes rates and a price change is picked up automatically.
 */
final class PixelforgePricing
{
    private const CACHE_KEY = 'pixelforge.pricing';

    private const CACHE_SECONDS = 600;

    public function sheet(): PixelforgePriceSheet
    {
        $sheet = Cache::get(self::CACHE_KEY);

        if ($sheet instanceof PixelforgePriceSheet) {
            return $sheet;
        }

        $sheet = $this->fetch();
        Cache::put(self::CACHE_KEY, $sheet, self::CACHE_SECONDS);

        return $sheet;
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function fetch(): PixelforgePriceSheet
    {
        $baseUrl = rtrim((string) config('prism.providers.pixelforge.url', 'https://pixelforge.promptedit.dev/v1'), '/');

        $response = Http::baseUrl($baseUrl)
            ->acceptJson()
            ->timeout(10)
            ->retry(2, 200)
            ->get('/pricing');

        if ($response->failed() || ! is_array($response->json())) {
            throw new RuntimeException('Pixelforge pricing is temporarily unavailable. Please try again shortly.');
        }

        return PixelforgePriceSheet::fromApi($response->json());
    }
}

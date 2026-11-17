<?php

namespace App\Services;

use App\Models\PixelforgeUsageDay;
use App\Prism\Providers\PixelForgeProvider;
use App\Support\Money;
use Illuminate\Support\Collection;
use Prism\Prism\Exceptions\PrismException;
use Prism\Prism\PrismManager;
use Throwable;

/**
 * Syncs Pixelforge's per-day usage report (GET /v1/usage) into the database
 * and serves it back from there: the DB is where the app gets its usage —
 * reports never depend on the provider being reachable at read time.
 */
final class UsageDaySync
{
    /**
     * Fetch the provider's report for the inclusive UTC date range and upsert
     * one row per day. Returns false when the provider could not be reached;
     * previously synced days remain in the database.
     */
    public function sync(string $startDate, string $endDate): bool
    {
        $usage = $this->fetch($startDate, $endDate);

        if ($usage === null) {
            return false;
        }

        $now = now();

        foreach ($usage['data'] as $row) {
            PixelforgeUsageDay::updateOrCreate(
                ['date' => $row['date']],
                [
                    'generations' => $row['generations'],
                    'input_text_tokens' => $row['input_text_tokens'],
                    'input_image_tokens' => $row['input_image_tokens'],
                    'output_tokens' => $row['output_tokens'],
                    'reasoning_tokens' => $row['reasoning_tokens'],
                    'cost_nd' => Money::fromString($row['cost_usd']),
                    'fetched_at' => $now,
                ],
            );
        }

        return true;
    }

    /**
     * The synced rows for an inclusive UTC date range, straight from the DB.
     *
     * @return Collection<int, PixelforgeUsageDay>
     */
    public function days(string $startDate, string $endDate): Collection
    {
        return PixelforgeUsageDay::query()
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->orderBy('date')
            ->get();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetch(string $startDate, string $endDate): ?array
    {
        try {
            $provider = app(PrismManager::class)->resolve('pixelforge');

            if (! $provider instanceof PixelForgeProvider) {
                return null;
            }

            return $provider->usage($startDate, $endDate);
        } catch (PrismException|Throwable) {
            return null;
        }
    }
}

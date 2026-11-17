<?php

namespace App\Console\Commands;

use App\Services\UsageDaySync;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Pull Pixelforge's per-day usage report into the database. Scheduled
 * daily and callable ad hoc; the admin report reads usage from the DB.
 */
final class PixelforgeSyncUsageCommand extends Command
{
    protected $signature = 'pixelforge:sync-usage
                            {--start= : Start date YYYY-MM-DD (inclusive, defaults to 13 days ago)}
                            {--end= : End date YYYY-MM-DD (inclusive, defaults to today)}';

    protected $description = 'Sync Pixelforge usage reports into the database';

    public function handle(UsageDaySync $sync): int
    {
        $end = $this->option('end') !== null
            ? Carbon::createFromFormat('Y-m-d', (string) $this->option('end'), 'UTC')->toDateString()
            : Carbon::now('UTC')->toDateString();
        $start = $this->option('start') !== null
            ? Carbon::createFromFormat('Y-m-d', (string) $this->option('start'), 'UTC')->toDateString()
            : Carbon::createFromFormat('Y-m-d', $end, 'UTC')->subDays(13)->toDateString();

        $synced = $sync->sync($start, $end);
        $days = $sync->days($start, $end)->count();

        if ($synced) {
            $this->info("Synced Pixelforge usage for {$start} to {$end} ({$days} day rows).");

            return self::SUCCESS;
        }

        $this->error("Could not reach Pixelforge for usage between {$start} and {$end}.");

        return self::FAILURE;
    }
}

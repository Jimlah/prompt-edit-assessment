<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Webhook delivery is not guaranteed, and a worker can die mid-generation.
// Reconcile generations that have been accepted by the provider but are not
// terminal, so their state and costs are never left behind.
Schedule::command('pixelforge:reconcile-stale')->everyMinute();

// Keep the per-day usage snapshot in the database fresh for the admin report.
Schedule::command('pixelforge:sync-usage')->dailyAt('00:05');

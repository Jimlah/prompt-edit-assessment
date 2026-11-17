<?php

namespace App\Console\Commands;

use App\Enums\GenerationStatus;
use App\Models\Generation;
use App\Prism\Providers\PixelForgeProvider;
use App\Services\GenerationSync;
use Illuminate\Console\Command;
use Prism\Prism\PrismManager;
use RuntimeException;
use Throwable;

/**
 * Catches generations accepted by Pixelforge that are still not terminal —
 * e.g. every webhook attempt failed, or the queue worker died mid-poll —
 * and applies the provider's state through the same guarded sync.
 */
final class PixelforgeReconcileStaleCommand extends Command
{
    protected $signature = 'pixelforge:reconcile-stale
                            {--stale-after=5 : Minutes since the last update before a generation is considered stale}';

    protected $description = 'Reconcile generations that are stuck in a non-terminal status with Pixelforge';

    public function handle(PrismManager $manager, GenerationSync $sync): int
    {
        $provider = $manager->resolve('pixelforge');

        if (! $provider instanceof PixelForgeProvider) {
            throw new RuntimeException('The Pixelforge provider is not registered correctly.');
        }

        $staleAfter = max(1, (int) $this->option('stale-after'));

        $stale = Generation::query()
            ->whereIn('status', GenerationStatus::values(GenerationStatus::Queued, GenerationStatus::Processing))
            ->whereNotNull('provider_id')
            ->where('updated_at', '<=', now()->subMinutes($staleAfter))
            ->orderBy('updated_at')
            ->limit(50)
            ->get();

        if ($stale->isEmpty()) {
            return self::SUCCESS;
        }

        $reconciled = 0;

        foreach ($stale as $generation) {
            try {
                $providerGeneration = $provider->getGeneration((string) $generation->provider_id);
            } catch (Throwable $e) {
                $this->warn("Could not fetch generation {$generation->provider_id}: {$e->getMessage()}");

                continue;
            }

            $status = GenerationStatus::tryFrom($providerGeneration['status']);

            match ($status) {
                GenerationStatus::Succeeded => $sync->markSucceeded($generation, $providerGeneration),
                GenerationStatus::Failed => $this->failFromProvider($sync, $generation, $providerGeneration),
                GenerationStatus::Processing => $sync->markProcessing($generation, $providerGeneration['id']),
                default => null,
            };

            $reconciled++;
        }

        $this->info("Reconciled {$reconciled} of {$stale->count()} stale generation(s).");

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $providerGeneration
     */
    private function failFromProvider(GenerationSync $sync, Generation $generation, array $providerGeneration): void
    {
        $error = is_array($providerGeneration['error'] ?? null) ? $providerGeneration['error'] : [];

        $sync->markFailed(
            $generation,
            is_string($error['code'] ?? null) ? (string) $error['code'] : 'generation_failed',
            is_string($error['message'] ?? null) ? (string) $error['message'] : 'PixelForge could not generate this image.',
        );
    }
}

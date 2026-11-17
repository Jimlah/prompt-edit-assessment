<?php

namespace App\Jobs;

use App\Enums\GenerationStatus;
use App\Exceptions\PixelforgePermanentException;
use App\Exceptions\PixelforgeThrottledException;
use App\Models\Generation;
use App\Prism\Providers\PixelForgeProvider;
use App\Services\GenerationSync;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Prism\Prism\Exceptions\PrismException;
use Prism\Prism\PrismManager;
use RuntimeException;
use Throwable;

/**
 * Submits the generation to Pixelforge and follows it to completion.
 *
 * The submission is idempotent (keyed on the local generation id), so retries
 * never create or bill twice. Webhooks normally carry the result; this job
 * polls as the reliable fallback and both paths converge in GenerationSync.
 */
class GenerateImage implements ShouldQueue
{
    use Queueable;

    public int $tries = 10;

    public int $timeout = 300;

    /** How many polls per attempt (2s each) before the attempt times out. */
    protected int $polls = 60;

    public function __construct(public Generation $generation) {}

    public function handle(PrismManager $manager, GenerationSync $sync): void
    {
        $provider = $manager->resolve('pixelforge');

        if (! $provider instanceof PixelForgeProvider) {
            throw new RuntimeException('The Pixelforge provider is not registered correctly.');
        }

        try {
            $providerGeneration = $provider->createGeneration(
                prompt: $this->generation->prompt,
                size: $this->generation->size,
                quality: $this->generation->quality,
                referenceImages: $this->generation->reference_images ?? [],
                metadata: [
                    'generation_id' => $this->generation->id,
                    'user_id' => (string) $this->generation->user_id,
                    'source' => 'intellect-dashboard',
                ],
                idempotencyKey: $this->generation->id,
            );

            // Pin the provider id and the rates fixed at creation.
            $this->generation->update([
                'provider_id' => $providerGeneration['id'],
                'rates' => $providerGeneration['rates'] ?? null,
            ]);

            // Follow the generation to a terminal state.
            for ($attempt = 0; $attempt < $this->polls; $attempt++) {
                $status = GenerationStatus::tryFrom($providerGeneration['status']);

                match ($status) {
                    GenerationStatus::Processing => $sync->markProcessing($this->generation, $providerGeneration['id']),
                    GenerationStatus::Succeeded => $sync->markSucceeded($this->generation, $providerGeneration),
                    GenerationStatus::Failed => $this->failFromProvider($sync, $providerGeneration),
                    default => null,
                };

                if ($status !== null && $status->isTerminal()) {
                    return;
                }

                Sleep::sleep(max(1, $provider->pollInterval()));

                $providerGeneration = $provider->getGeneration($providerGeneration['id']);
            }

            throw PrismException::providerResponseError(
                'PixelForge did not finish the generation in time. It will be reconciled from the stored generation id.',
                504,
            );
        } catch (PixelforgePermanentException $e) {
            $sync->markFailed($this->generation, $e->errorCode, Str::limit($e->errorMessage, 300));

            return;
        } catch (PixelforgeThrottledException $e) {
            $this->handleThrottled($sync, $e);

            return;
        }
    }

    /**
     * Wait as long as the provider told us to, then try again. If every
     * attempt is exhausted, or the constraint will not lift by waiting
     * (the daily quota), the generation fails and costs nothing.
     */
    protected function handleThrottled(GenerationSync $sync, PixelforgeThrottledException $e): void
    {
        if ($e->shouldGiveUp() || $this->attempts() >= $this->tries) {
            $sync->markFailed($this->generation, $e->errorCode, Str::limit($e->errorMessage, 300));

            return;
        }

        $this->release(min(max(1, $e->retryAfter), 300));
    }

    /**
     * @param  array<string, mixed>  $providerGeneration
     */
    protected function failFromProvider(GenerationSync $sync, array $providerGeneration): void
    {
        $error = is_array($providerGeneration['error'] ?? null) ? $providerGeneration['error'] : [];

        $sync->markFailed(
            $this->generation,
            is_string($error['code'] ?? null) ? (string) $error['code'] : 'generation_failed',
            is_string($error['message'] ?? null) ? (string) $error['message'] : 'PixelForge could not generate this image.',
        );
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [5, 15, 30, 60, 120, 300, 300, 300, 300];
    }

    public function failed(?Throwable $exception): void
    {
        try {
            $this->generation->refresh();
        } catch (Throwable) {
            return; // The user was deleted; cascades cleaned up the row.
        }

        app(GenerationSync::class)->markFailed(
            $this->generation,
            'generation_failed',
            Str::limit((string) ($exception?->getMessage() ?? 'The image could not be created.'), 300),
        );
    }
}

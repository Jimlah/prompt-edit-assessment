<?php

namespace App\Jobs;

use App\Models\Generation;
use App\Models\WebhookEvent;
use App\Services\GenerationSync;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Applies one Pixelforge webhook event to the local record.
 *
 * Events can arrive twice (deduplicated upstream) and out of order, so all
 * state changes go through GenerationSync's guarded updates: a generation can
 * only move forward, and its costs are recorded at most once.
 */
final class ProcessPixelforgeEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public string $webhookEventId) {}

    public function handle(GenerationSync $sync, ?LoggerInterface $log = null): void
    {
        $log ??= Log::getLogger();

        $event = WebhookEvent::query()->find($this->webhookEventId);

        if ($event === null || $event->processed_at !== null) {
            return;
        }

        $object = $event->payload['data']['object'] ?? null;

        if ($event->type === 'ping' || ! is_array($object) || ! is_string($object['id'] ?? null)) {
            $event->update(['processed_at' => now()]);

            return;
        }

        $generation = $this->findGeneration($event, $object);

        if ($generation === null) {
            $log->warning('Pixelforge webhook event for an unknown generation.', [
                'event_id' => $event->event_id,
                'type' => $event->type,
                'provider_generation_id' => $object['id'],
            ]);
            $event->update(['processed_at' => now()]);

            return;
        }

        match ($event->type) {
            'generation.processing' => $sync->markProcessing($generation, $object['id']),
            'generation.succeeded' => $sync->markSucceeded($generation, $object),
            'generation.failed' => $this->applyFailure($sync, $generation, $object),
            default => null,
        };

        $event->update(['processed_at' => now()]);
    }

    /**
     * The webhook may arrive before our create request has returned, so match
     * by the metadata we attached first, then by the provider id.
     *
     * @param  array<string, mixed>  $object
     */
    private function findGeneration(WebhookEvent $event, array $object): ?Generation
    {
        $metadata = is_array($object['metadata'] ?? null) ? $object['metadata'] : [];
        $localId = $metadata['generation_id'] ?? null;

        if (is_string($localId) && $localId !== '') {
            $generation = Generation::query()->find($localId);

            if ($generation !== null) {
                return $generation;
            }
        }

        return Generation::query()->where('provider_id', $object['id'])->first();
    }

    /**
     * @param  array<string, mixed>  $object
     */
    private function applyFailure(GenerationSync $sync, Generation $generation, array $object): void
    {
        $error = is_array($object['error'] ?? null) ? $object['error'] : [];

        $sync->markFailed(
            $generation,
            is_string($error['code'] ?? null) ? (string) $error['code'] : 'generation_failed',
            is_string($error['message'] ?? null) ? (string) $error['message'] : 'PixelForge could not generate this image.',
        );
    }
}

<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPixelforgeEvent;
use App\Models\WebhookEvent;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Receives verified Pixelforge webhooks (signature is checked by middleware).
 * Delivery is at-least-once, so events are deduplicated by their unique id,
 * and processing is queued so this endpoint can answer inside the deadline.
 */
final class PixelforgeWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        // Signature middleware has verified the raw body; decode it now.
        $event = json_decode((string) $request->getContent(), true);

        if (! is_array($event)
            || ! is_string($event['id'] ?? null)
            || ! is_string($event['type'] ?? null)) {
            abort(400, 'Invalid Pixelforge event.');
        }

        $object = is_array($event['data'] ?? null) ? ($event['data']['object'] ?? null) : null;
        $providerGenerationId = is_array($object) && is_string($object['id'] ?? null) ? $object['id'] : null;

        try {
            $record = WebhookEvent::create([
                'event_id' => $event['id'],
                'type' => $event['type'],
                'provider_generation_id' => $providerGenerationId,
                'payload' => $event,
            ]);
        } catch (UniqueConstraintViolationException) {
            // The same event was delivered more than once. It is already
            // queued for processing: never act on it twice.
            return response()->noContent();
        }

        ProcessPixelforgeEvent::dispatch($record->id);

        return response()->noContent();
    }
}

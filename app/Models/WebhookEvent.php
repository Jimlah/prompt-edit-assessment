<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Every Pixelforge webhook event we have received. The unique event_id is the
 * dedupe key: delivery is at-least-once, so the same event can arrive many
 * times and must only be processed once.
 *
 * @property string $id
 * @property string $event_id Pixelforge event id (evt_…), unique.
 * @property string $type
 * @property ?string $provider_generation_id
 * @property array<string, mixed> $payload
 * @property ?Carbon $processed_at
 */
#[Fillable(['event_id', 'type', 'provider_generation_id', 'payload', 'processed_at'])]
class WebhookEvent extends Model
{
    use HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}

<?php

use App\Enums\GenerationStatus;
use App\Jobs\ProcessPixelforgeEvent;
use App\Models\BalanceTransaction;
use App\Models\Generation;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\GenerationSync;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function eventGenerationObject(string $providerId, string $localGenerationId, array $usage, string $status = 'succeeded'): array
{
    return [
        'id' => $providerId,
        'object' => 'generation',
        'status' => $status,
        'prompt' => 'A glowing woodland lantern',
        'size' => '1024x1024',
        'quality' => 'high',
        'reference_images' => [],
        'metadata' => [
            'generation_id' => $localGenerationId,
            'user_id' => '1',
        ],
        'image_url' => 'https://pixelforge.test/images/lantern.png',
        'image_url_expires_at' => '2026-11-18T14:03:29Z',
        'usage' => $usage,
        'rates' => [
            'input_text_tokens' => '5.00',
            'input_image_tokens' => '10.00',
            'output_tokens' => '40.00',
            'reasoning_tokens' => '12.50',
        ],
        'error' => null,
        'completed_at' => '2026-11-17T14:03:29Z',
        'created_at' => '2026-11-17T14:03:11Z',
    ];
}

function storeWebhookEvent(string $type, array $object): WebhookEvent
{
    return WebhookEvent::create([
        'event_id' => 'evt_'.bin2hex(random_bytes(8)),
        'type' => $type,
        'provider_generation_id' => $object['id'],
        'payload' => ['id' => 'evt_source', 'type' => $type, 'data' => ['object' => $object]],
    ]);
}

function runEvent(string $type, array $object): ProcessPixelforgeEvent
{
    $event = storeWebhookEvent($type, $object);

    (new ProcessPixelforgeEvent($event->id))->handle(app(GenerationSync::class));

    $event->refresh();
    expect($event->processed_at)->not->toBeNull();

    return new ProcessPixelforgeEvent($event->id);
}

function usage(array $overrides = []): array
{
    return array_merge([
        'input_text_tokens' => 8,
        'input_image_tokens' => 765,
        'output_tokens' => 1100,
        'reasoning_tokens' => 250,
        'total_tokens' => 2123,
    ], $overrides);
}

test('a succeeded webhook records usage, costs and charges the balance', function (): void {
    $user = User::factory()->withBalance(1000000000)->create();
    $generation = Generation::factory()->for($user)->create();
    $object = eventGenerationObject('gen_evt_ok', $generation->id, usage());

    runEvent('generation.succeeded', $object);

    $generation->refresh();

    // 8*5e3 + 765*1e4 + 1100*4e4 + 250*1.25e4 = 54,815,000 nd provider cost.
    expect($generation->status)->toBe(GenerationStatus::Succeeded)
        ->and($generation->provider_id)->toBe('gen_evt_ok')
        ->and($generation->usage['total_tokens'])->toBe(2123)
        ->and($generation->provider_cost_nd)->toBe(54815000)
        ->and($generation->user_cost_nd)->toBe(68518750)
        ->and($user->refresh()->credit_balance_nd)->toBe(1000000000 - 68518750)
        ->and(BalanceTransaction::where('type', 'charge')->count())->toBe(1);
});

test('a delivered-twice success never records or charges twice', function (): void {
    $user = User::factory()->withBalance(1000000000)->create();
    $generation = Generation::factory()->for($user)->create();
    $object = eventGenerationObject('gen_evt_dup', $generation->id, usage());

    runEvent('generation.succeeded', $object);

    // The provider retries, each delivery signed but with a NEW event id:
    // only the generation-level guard stops a second charge.
    $second = storeWebhookEvent('generation.succeeded', $object);
    (new ProcessPixelforgeEvent($second->id))->handle(app(GenerationSync::class));

    $generation->refresh();
    expect($generation->user_cost_nd)->toBe(68518750)
        ->and($user->refresh()->credit_balance_nd)->toBe(1000000000 - 68518750)
        ->and(BalanceTransaction::where('type', 'charge')->count())->toBe(1);
});

test('out-of-order webhooks never regress a terminal generation', function (): void {
    $user = User::factory()->withBalance(1000000000)->create();
    $generation = Generation::factory()->for($user)->create();
    $object = eventGenerationObject('gen_evt_order', $generation->id, usage([
        'input_text_tokens' => 10,
        'input_image_tokens' => 0,
        'output_tokens' => 1100,
        'reasoning_tokens' => 500,
        'total_tokens' => 1610,
    ]));

    runEvent('generation.succeeded', $object);

    // A retried "processing" arrives later and must be ignored.
    runEvent('generation.processing', $object);

    $generation->refresh();
    expect($generation->status)->toBe(GenerationStatus::Succeeded)
        ->and(BalanceTransaction::where('type', 'charge')->count())->toBe(1);
});

test('a failed webhook records the error and charges nothing', function (): void {
    $user = User::factory()->withBalance()->create();
    $generation = Generation::factory()->for($user)->create();
    $object = eventGenerationObject('gen_evt_failed', $generation->id, usage([
        'input_text_tokens' => 0,
        'input_image_tokens' => 0,
        'output_tokens' => 0,
        'reasoning_tokens' => 0,
        'total_tokens' => 0,
    ]), 'failed');
    $object['image_url'] = null;
    $object['usage'] = null;
    $object['error'] = ['code' => 'generation_failed', 'message' => 'Model could not draw.'];

    runEvent('generation.failed', $object);

    $generation->refresh();
    expect($generation->status)->toBe(GenerationStatus::Failed)
        ->and($generation->error['code'])->toBe('generation_failed')
        ->and($generation->error['message'])->toBe('Model could not draw.')
        ->and($generation->completed_at)->not->toBeNull()
        ->and(BalanceTransaction::count())->toBe(0)
        ->and($user->refresh()->credit_balance_nd)->toBe(5000000000);
});

test('success after a locally-failed generation still records the charge', function (): void {
    // A worker can die mid-poll and write a local failure while the provider
    // actually succeeded. Billing correctness wins: the late success still
    // records usage and cost.
    $user = User::factory()->withBalance(1000000000)->create();
    $generation = Generation::factory()->failed()->for($user)->create();
    $object = eventGenerationObject((string) $generation->provider_id, $generation->id, usage([
        'input_text_tokens' => 10,
        'input_image_tokens' => 0,
        'output_tokens' => 1100,
        'reasoning_tokens' => 500,
        'total_tokens' => 1610,
    ]));

    runEvent('generation.succeeded', $object);

    $generation->refresh();
    expect($generation->status)->toBe(GenerationStatus::Succeeded)
        // 10*5e3 + 1100*4e4 + 500*1.25e4 = 50,300,000 nd provider cost.
        ->and($generation->user_cost_nd)->toBe(62875000)
        ->and($user->refresh()->credit_balance_nd)->toBe(1000000000 - 62875000);
});

test('processing webhooks move queued generations forward', function (): void {
    $user = User::factory()->create();
    $generation = Generation::factory()->for($user)->create();
    $object = eventGenerationObject('gen_evt_processing', $generation->id, usage(), 'processing');
    $object['image_url'] = null;
    $object['usage'] = null;
    $object['completed_at'] = null;

    runEvent('generation.processing', $object);

    $generation->refresh();
    expect($generation->status)->toBe(GenerationStatus::Processing)
        ->and($generation->provider_id)->toBe('gen_evt_processing');
});

test('events without a matching generation are processed harmlessly', function (): void {
    $event = WebhookEvent::create([
        'event_id' => 'evt_orphan',
        'type' => 'generation.succeeded',
        'payload' => ['type' => 'generation.succeeded', 'data' => ['object' => ['id' => 'gen_ghost', 'metadata' => []]]],
    ]);

    (new ProcessPixelforgeEvent($event->id))->handle(app(GenerationSync::class));

    expect($event->refresh()->processed_at)->not->toBeNull();
});

test('ping events are marked processed without generation work', function (): void {
    $generation = Generation::factory()->for(User::factory())->create();
    $event = WebhookEvent::create(['event_id' => 'evt_ping', 'type' => 'ping', 'payload' => ['type' => 'ping']]);

    (new ProcessPixelforgeEvent($event->id))->handle(app(GenerationSync::class));

    expect($event->refresh()->processed_at)->not->toBeNull()
        ->and($generation->refresh()->status)->toBe(GenerationStatus::Queued);
});

test('already processed events are never processed again', function (): void {
    $user = User::factory()->withBalance(1000000000)->create();
    $generation = Generation::factory()->for($user)->create();
    $event = storeWebhookEvent('generation.succeeded', eventGenerationObject('gen_once', $generation->id, usage()));

    (new ProcessPixelforgeEvent($event->id))->handle(app(GenerationSync::class));
    $firstCharge = $user->refresh()->credit_balance_nd;

    // Simulate a re-dispatch of the same job (queue retry): the processed_at
    // marker short-circuits before any generation work.
    (new ProcessPixelforgeEvent($event->id))->handle(app(GenerationSync::class));

    expect($user->refresh()->credit_balance_nd)->toBe($firstCharge)
        ->and(BalanceTransaction::count())->toBe(1);
});

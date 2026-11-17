<?php

use App\Enums\GenerationStatus;
use App\Exceptions\PixelforgePermanentException;
use App\Exceptions\PixelforgeThrottledException;
use App\Jobs\GenerateImage;
use App\Models\BalanceTransaction;
use App\Models\Generation;
use App\Models\User;
use App\Services\GenerationSync;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Prism\Prism\PrismManager;

uses(LazilyRefreshDatabase::class);

function providerGeneration(string $id, string $status, array $overrides = []): array
{
    return array_merge([
        'id' => $id,
        'object' => 'generation',
        'status' => $status,
        'prompt' => 'A glowing woodland lantern',
        'size' => '1024x1024',
        'quality' => 'high',
        'reference_images' => [],
        'metadata' => [],
        'image_url' => null,
        'image_url_expires_at' => null,
        'usage' => null,
        'rates' => [
            'input_text_tokens' => '5.00',
            'input_image_tokens' => '10.00',
            'output_tokens' => '40.00',
            'reasoning_tokens' => '12.50',
        ],
        'error' => null,
        'completed_at' => '2026-11-17T14:03:29Z',
    ], $overrides);
}

function runJob(Generation $generation): void
{
    (new GenerateImage($generation))->handle(app(PrismManager::class), app(GenerationSync::class));
}

test('queued generation completes, stores exact usage and charges the user', function (): void {
    $user = User::factory()->withBalance(1000000000)->create();
    $generation = Generation::factory()->for($user)->create([
        'prompt' => 'A glowing woodland lantern',
        'reference_images' => ['https://example.com/reference.png'],
    ]);
    config()->set('prism.providers.pixelforge.api_key', 'test-key');
    config()->set('prism.providers.pixelforge.url', 'https://pixelforge.test/v1');
    Http::preventStrayRequests();
    Http::fake([
        'https://pixelforge.test/v1/generations' => Http::response(
            providerGeneration('gen_abc123', 'queued', ['metadata' => ['generation_id' => $generation->id]]),
            202,
        ),
        'https://pixelforge.test/v1/generations/gen_abc123' => Http::sequence([
            Http::response(providerGeneration('gen_abc123', 'processing')),
            Http::response(providerGeneration('gen_abc123', 'succeeded', [
                'image_url' => 'https://pixelforge.test/images/lantern.png',
                'image_url_expires_at' => '2026-11-18T14:03:29Z',
                'usage' => [
                    'input_text_tokens' => 8,
                    'input_image_tokens' => 765,
                    'output_tokens' => 1100,
                    'reasoning_tokens' => 250,
                    'total_tokens' => 2123,
                ],
            ])),
        ]),
    ]);
    Sleep::fake();

    runJob($generation);
    $generation->refresh();

    // Exact provider cost from stored rates: 8*5e3 + 765*1e4 + 1100*4e4 + 250*1.25e4
    // = 40,000 + 7,650,000 + 44,000,000 + 3,125,000 = 54,815,000 nanodollars.
    // User cost adds the 25% markup: 68,518,750 nanodollars.
    expect($generation->status)->toBe(GenerationStatus::Succeeded)
        ->and($generation->provider_id)->toBe('gen_abc123')
        ->and($generation->image_url)->toBe('https://pixelforge.test/images/lantern.png')
        ->and($generation->usage['input_text_tokens'])->toBe(8)
        ->and($generation->usage['input_image_tokens'])->toBe(765)
        ->and($generation->usage['total_tokens'])->toBe(2123)
        ->and($generation->provider_cost_nd)->toBe(54815000)
        ->and($generation->user_cost_nd)->toBe(68518750)
        ->and($generation->error)->toBeNull()
        ->and($generation->completed_at->format('Y-m-d\TH:i'))->toBe('2026-11-17T14:03')
        // The job updates the database, not this in-memory model.
        ->and($user->refresh()->credit_balance_nd)->toBe(1000000000 - 68518750);

    Sleep::assertSleptTimes(2);
    Http::assertSentCount(3);
    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request['prompt'] === 'A glowing woodland lantern'
        && $request['reference_images'] === ['https://example.com/reference.png']);
    expect(BalanceTransaction::where('user_id', $user->id)->where('type', 'charge')->count())->toBe(1);
    expect(BalanceTransaction::where('user_id', $user->id)->first()->amount_nd)->toBe(-68518750);
});

test('job retries never re-charge a completed generation', function (): void {
    $user = User::factory()->withBalance(1000000000)->create();
    $generation = Generation::factory()->for($user)->create([
        'prompt' => 'A glowing woodland lantern',
    ]);
    config()->set('prism.providers.pixelforge.api_key', 'test-key');
    config()->set('prism.providers.pixelforge.url', 'https://pixelforge.test/v1');
    Http::preventStrayRequests();
    $succeededPayload = providerGeneration('gen_abc123', 'succeeded', [
        'image_url' => 'https://pixelforge.test/images/lantern.png',
        'usage' => [
            'input_text_tokens' => 8,
            'input_image_tokens' => 0,
            'output_tokens' => 1100,
            'reasoning_tokens' => 250,
            'total_tokens' => 1358,
        ],
    ]);
    Http::fake([
        'https://pixelforge.test/v1/generations' => Http::response($succeededPayload, 202),
        'https://pixelforge.test/v1/generations/gen_abc123' => Http::response($succeededPayload, 200),
    ]);
    Sleep::fake();

    runJob($generation);
    $firstBalance = $user->refresh()->credit_balance_nd;

    // A duplicate job for the same generation is a no-op: idempotent replay
    // returns the same result and the guard prevents a second charge.
    runJob($generation);

    expect($generation->refresh()->status)->toBe(GenerationStatus::Succeeded)
        ->and($user->refresh()->credit_balance_nd)->toBe($firstBalance)
        ->and(BalanceTransaction::where('type', 'charge')->count())->toBe(1);
});

test('failed provider generations record the provider error and cost nothing', function (): void {
    $user = User::factory()->withBalance()->create();
    $generation = Generation::factory()->for($user)->create();
    config()->set('prism.providers.pixelforge.api_key', 'test-key');
    config()->set('prism.providers.pixelforge.url', 'https://pixelforge.test/v1');
    Http::preventStrayRequests();
    Http::fake([
        'https://pixelforge.test/v1/generations' => Http::response(
            providerGeneration('gen_fail1', 'queued'),
            202,
        ),
        'https://pixelforge.test/v1/generations/gen_fail1' => Http::response(providerGeneration('gen_fail1', 'failed', [
            'error' => ['code' => 'generation_failed', 'message' => 'The image could not be drawn.'],
        ])),
    ]);

    runJob($generation);

    $generation->refresh();
    expect($generation->status)->toBe(GenerationStatus::Failed)
        ->and($generation->error['code'])->toBe('generation_failed')
        ->and($generation->error['message'])->toBe('The image could not be drawn.')
        ->and($generation->provider_cost_nd)->toBeNull()
        ->and($user->refresh()->credit_balance_nd)->toBe(5000000000)
        ->and(BalanceTransaction::count())->toBe(0);
});

test('content policy rejections fail fast without retrying', function (): void {
    $generation = Generation::factory()->create();
    config()->set('prism.providers.pixelforge.api_key', 'test-key');
    config()->set('prism.providers.pixelforge.url', 'https://pixelforge.test/v1');
    Http::preventStrayRequests();
    Http::fake([
        'https://pixelforge.test/v1/generations' => Http::response([
            'error' => ['type' => 'invalid_request_error', 'code' => 'content_policy_violation', 'message' => 'The prompt was rejected.'],
        ], 400),
    ]);

    runJob($generation);

    $generation->refresh();
    expect($generation->status)->toBe(GenerationStatus::Failed)
        ->and($generation->error['code'])->toBe('content_policy_violation')
        ->and($generation->error['message'])->toBe('The prompt was rejected.')
        ->and(Http::assertSentCount(1));
});

test('daily quota exhaustion fails without pointless retries', function (): void {
    $generation = Generation::factory()->create();
    config()->set('prism.providers.pixelforge.api_key', 'test-key');
    config()->set('prism.providers.pixelforge.url', 'https://pixelforge.test/v1');
    Http::preventStrayRequests();
    Http::fake([
        'https://pixelforge.test/v1/generations' => Http::response([
            'error' => ['type' => 'rate_limit_error', 'code' => 'daily_limit_exceeded', 'message' => 'The daily quota is used up.'],
        ], 429, ['Retry-After' => '86400']),
    ]);

    runJob($generation);

    $generation->refresh();
    expect($generation->status)->toBe(GenerationStatus::Failed)
        ->and($generation->error['code'])->toBe('daily_limit_exceeded')
        ->and(Http::assertSentCount(1));
});

test('concurrency limits wait and retry instead of failing', function (): void {
    $generation = Generation::factory()->create();
    config()->set('prism.providers.pixelforge.api_key', 'test-key');
    config()->set('prism.providers.pixelforge.url', 'https://pixelforge.test/v1');
    Http::preventStrayRequests();
    Http::fake([
        'https://pixelforge.test/v1/generations' => Http::response([
            'error' => ['type' => 'rate_limit_error', 'code' => 'concurrency_limit_exceeded', 'message' => 'Too many generations.'],
        ], 429, ['Retry-After' => '5']),
    ]);

    runJob($generation);

    $generation->refresh();
    expect($generation->status)->toBe(GenerationStatus::Queued)
        ->and($generation->error)->toBeNull()
        ->and(Http::assertSentCount(1));
});

test('permanent and throttled exceptions carry their codes', function (): void {
    $permanent = new PixelforgePermanentException('content_policy_violation', 'Blocked.');
    expect($permanent->errorCode)->toBe('content_policy_violation')
        ->and($permanent->errorMessage)->toBe('Blocked.');

    $throttled = new PixelforgeThrottledException('concurrency_limit_exceeded', 'Busy.', 5);
    expect($throttled->shouldGiveUp())->toBeFalse()
        ->and((new PixelforgeThrottledException('daily_limit_exceeded', 'Quota.', 5))->shouldGiveUp())->toBeTrue()
        ->and((new PixelforgeThrottledException('rate_limit_exceeded', 'Slow down.', 1000))->shouldGiveUp())->toBeTrue();
});

test('exhausted retries mark the generation failed', function (): void {
    $generation = Generation::factory()->create();

    (new GenerateImage($generation))->failed(new RuntimeException('connection reset'));

    $generation->refresh();
    expect($generation->status)->toBe(GenerationStatus::Failed)
        ->and($generation->error['message'])->toBe('connection reset')
        ->and($generation->provider_cost_nd)->toBeNull()
        ->and($generation->user_cost_nd)->toBeNull();
});

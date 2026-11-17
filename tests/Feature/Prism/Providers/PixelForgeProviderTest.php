<?php

use App\Exceptions\PixelforgePermanentException;
use App\Exceptions\PixelforgeThrottledException;
use App\Prism\Providers\PixelForgeProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Prism\Prism\Exceptions\PrismException;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\ImageResponseFake;
use Prism\Prism\ValueObjects\GeneratedImage;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\Usage;

function pixelProviderGeneration(string $id, string $status, array $overrides = []): array
{
    return array_merge([
        'id' => $id,
        'object' => 'generation',
        'status' => $status,
        'prompt' => 'A botanical glasshouse at night',
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
    ], $overrides);
}

test('synchronous image requests poll until the image is ready', function (): void {
    config()->set('prism.providers.pixelforge.api_key', 'test-key');
    config()->set('prism.providers.pixelforge.url', 'https://pixelforge.test/v1');
    Http::preventStrayRequests();
    Http::fake([
        'https://pixelforge.test/v1/generations' => Http::response(
            pixelProviderGeneration('gen_sync', 'queued'),
            202,
        ),
        'https://pixelforge.test/v1/generations/gen_sync' => Http::sequence([
            Http::response(pixelProviderGeneration('gen_sync', 'processing')),
            Http::response(pixelProviderGeneration('gen_sync', 'succeeded', [
                'image_url' => 'https://pixelforge.test/images/gen_sync.png',
                'usage' => [
                    'input_text_tokens' => 8,
                    'input_image_tokens' => 0,
                    'output_tokens' => 1100,
                    'reasoning_tokens' => 250,
                    'total_tokens' => 1358,
                ],
            ])),
        ]),
    ]);
    Sleep::fake();

    $response = Prism::image()
        ->using('pixelforge', 'pixelforge-xl')
        ->withPrompt('A botanical glasshouse at night')
        ->generate();

    expect($response->hasImages())->toBeTrue()
        ->and($response->firstImage()->url)->toBe('https://pixelforge.test/images/gen_sync.png')
        ->and($response->usage->promptTokens)->toBe(8)
        ->and($response->usage->completionTokens)->toBe(1100)
        ->and($response->usage->thoughtTokens)->toBe(250)
        // The full Pixelforge generation, including the rates fixed at
        // creation, is available for exact cost recording.
        ->and($response->raw['rates'])->toBe([
            'input_text_tokens' => '5.00',
            'input_image_tokens' => '10.00',
            'output_tokens' => '40.00',
            'reasoning_tokens' => '12.50',
        ])
        ->and($response->raw['usage']['total_tokens'])->toBe(1358);
    Sleep::assertSleptTimes(1);
    Http::assertSentCount(3);
});

test('synchronous image requests surface generation failures', function (): void {
    config()->set('prism.providers.pixelforge.api_key', 'test-key');
    config()->set('prism.providers.pixelforge.url', 'https://pixelforge.test/v1');
    Http::preventStrayRequests();
    Http::fake([
        'https://pixelforge.test/v1/generations' => Http::response(
            pixelProviderGeneration('gen_failed', 'queued'),
            202,
        ),
        'https://pixelforge.test/v1/generations/gen_failed' => Http::response(
            pixelProviderGeneration('gen_failed', 'failed', [
                'error' => [
                    'code' => 'generation_failed',
                    'message' => 'The image could not be drawn.',
                ],
            ]),
        ),
    ]);
    Sleep::fake();

    expect(fn () => Prism::image()
        ->using('pixelforge', 'pixelforge-xl')
        ->withPrompt('A botanical glasshouse at night')
        ->generate())
        ->toThrow(PrismException::class, 'The image could not be drawn.');

    Sleep::assertNeverSlept();
    Http::assertSentCount(2);
});

test('image generation through prism uses the provider with provider options', function (): void {
    config()->set('prism.providers.pixelforge.api_key', 'test-key');

    $fake = Prism::fake([
        ImageResponseFake::make()
            ->withImages([
                new GeneratedImage(url: 'https://example.com/fake-image.png', mimeType: 'image/png'),
            ])
            ->withUsage(new Usage(12, 1100, 250))
            ->withMeta(new Meta('gen_fake_1', 'pixelforge-xl')),
    ]);

    $response = Prism::image()
        ->using('pixelforge', 'pixelforge-xl')
        ->withPrompt('A botanical glasshouse at night')
        ->withProviderOptions([
            'size' => '1536x1024',
            'quality' => 'high',
            'reference_images' => ['https://example.com/reference.png'],
            'idempotency_key' => 'gen_local_uuid_1',
        ])
        ->generate();

    expect($response->hasImages())->toBeTrue()
        ->and($response->firstImage()->url)->toBe('https://example.com/fake-image.png');

    $fake->assertCallCount(1);
    $fake->assertPrompt('A botanical glasshouse at night');
    $fake->assertRequest(function (array $requests): void {
        $request = $requests[0];

        expect($request->provider())->toBe('pixelforge')
            ->and($request->model())->toBe('pixelforge-xl')
            ->and($request->providerOptions())->toMatchArray([
                'size' => '1536x1024',
                'quality' => 'high',
                'reference_images' => ['https://example.com/reference.png'],
                'idempotency_key' => 'gen_local_uuid_1',
            ]);
    });
});

test('provider failures distinguish permanent errors from throttling', function (): void {
    config()->set('prism.providers.pixelforge.api_key', 'test-key');
    config()->set('prism.providers.pixelforge.url', 'https://pixelforge.test/v1');

    Http::preventStrayRequests();
    Http::fake([
        '*' => Http::sequence()
            ->push(['error' => [
                'type' => 'rate_limit_error',
                'code' => 'concurrency_limit_exceeded',
                'message' => 'Too many generations.',
            ]], 429, ['Retry-After' => '5'])
            ->push(['error' => [
                'type' => 'invalid_request_error',
                'code' => 'content_policy_violation',
                'message' => 'The prompt was rejected.',
            ]], 400)
            ->push(['error' => [
                'type' => 'api_error',
                'code' => 'overloaded',
                'message' => 'Pixelforge is overloaded.',
            ]], 503, ['Retry-After' => '2']),
    ]);

    $provider = new PixelForgeProvider('test-key', 'https://pixelforge.test/v1');

    expect(fn () => $provider->createGeneration('A prompt'))
        ->toThrow(PixelforgeThrottledException::class, 'concurrency_limit_exceeded')
        ->and(fn () => $provider->createGeneration('A prompt'))
        ->toThrow(PixelforgePermanentException::class, 'content_policy_violation')
        ->and(fn () => $provider->createGeneration('A prompt'))
        ->toThrow(PixelforgeThrottledException::class, 'overloaded');
});

test('the usage report is fetched and validated', function (): void {
    config()->set('prism.providers.pixelforge.api_key', 'test-key');
    config()->set('prism.providers.pixelforge.url', 'https://pixelforge.test/v1');
    Http::preventStrayRequests();
    Http::fake([
        'https://pixelforge.test/v1/usage?start_date=2026-11-01&end_date=2026-11-02' => Http::response([
            'object' => 'usage',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-02',
            'data' => [
                ['date' => '2026-11-01', 'generations' => 3, 'input_text_tokens' => 41, 'input_image_tokens' => 0, 'output_tokens' => 3300, 'reasoning_tokens' => 2950, 'cost_usd' => '0.169080000'],
                ['date' => '2026-11-02', 'generations' => 0, 'input_text_tokens' => 0, 'input_image_tokens' => 0, 'output_tokens' => 0, 'reasoning_tokens' => 0, 'cost_usd' => '0.000000000'],
            ],
            'total' => ['date' => '', 'generations' => 3, 'input_text_tokens' => 41, 'input_image_tokens' => 0, 'output_tokens' => 3300, 'reasoning_tokens' => 2950, 'cost_usd' => '0.169080000'],
        ]),
    ]);

    $provider = new PixelForgeProvider('test-key', 'https://pixelforge.test/v1');
    $usage = $provider->usage('2026-11-01', '2026-11-02');

    expect(count($usage['data']))->toBe(2)
        ->and($usage['data'][0]['cost_usd'])->toBe('0.169080000')
        ->and($usage['total']['generations'])->toBe(3);
});

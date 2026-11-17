<?php

use App\Jobs\GenerateImage;
use App\Models\Generation;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

function validImagePayload(array $overrides = []): array
{
    return array_merge([
        'prompt' => 'A glowing lantern in a moonlit forest',
        'size' => '1024x1024',
        'quality' => 'high',
        'reference_images' => ['https://example.com/reference.png'],
    ], $overrides);
}

test('image generation redirects guests to login', function (): void {
    $this->post('/dashboard/images', validImagePayload())
        ->assertRedirect(route('login'));
});

test('image generation redirects back with an error when pixelforge is not configured', function (): void {
    $user = User::factory()->create();
    config()->set('prism.providers.pixelforge.api_key', '');
    fakePixelforgePricing();

    $this->actingAs($user)
        ->from('/dashboard')
        ->post('/dashboard/images', validImagePayload())
        ->assertRedirect('/dashboard')
        ->assertSessionHas(
            'error',
            'Image generation is temporarily unavailable. Please try again later.',
        );
});

test('image generation validates required fields', function (): void {
    $user = User::factory()->withBalance()->create();
    config()->set('prism.providers.pixelforge.api_key', 'test-key');
    fakePixelforgePricing();
    Queue::fake();

    $this->actingAs($user)
        ->from('/dashboard')
        ->post('/dashboard/images', [])
        ->assertRedirect('/dashboard')
        ->assertInvalid([
            'prompt' => 'The prompt field is required.',
            'size' => 'The size field is required.',
            'quality' => 'The quality field is required.',
        ]);

    Queue::assertNothingPushed();
});

test('image generation rejects unsupported options', function (): void {
    $user = User::factory()->withBalance()->create();
    config()->set('prism.providers.pixelforge.api_key', 'test-key');
    fakePixelforgePricing();
    Queue::fake();

    $this->actingAs($user)
        ->from('/dashboard')
        ->post('/dashboard/images', validImagePayload([
            'size' => '500x500',
            'quality' => 'ultra',
        ]))
        ->assertRedirect('/dashboard')
        ->assertInvalid(['size', 'quality']);

    Queue::assertNothingPushed();
});

test('image generation rejects invalid reference image urls and more than four references', function (): void {
    $user = User::factory()->withBalance()->create();
    config()->set('prism.providers.pixelforge.api_key', 'test-key');
    fakePixelforgePricing();
    Queue::fake();

    $this->actingAs($user)
        ->from('/dashboard')
        ->post('/dashboard/images', validImagePayload([
            'reference_images' => [
                'not-a-valid-url',
                'https://example.com/two.png',
                'https://example.com/three.png',
                'https://example.com/four.png',
                'https://example.com/five.png',
            ],
        ]))
        ->assertRedirect('/dashboard')
        ->assertInvalid(['reference_images', 'reference_images.0']);

    Queue::assertNothingPushed();
});

test('image generation stores, prices and queues the image', function (): void {
    $user = User::factory()->withBalance(10000000000)->create();
    config()->set('prism.providers.pixelforge.api_key', 'test-key');
    fakePixelforgePricing();
    Queue::fake();

    $this->actingAs($user)
        ->from('/dashboard')
        ->post('/dashboard/images', validImagePayload())
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', 'Image queued for generation.');

    $generation = Generation::firstOrFail();

    $this->assertDatabaseHas('generations', [
        'id' => $generation->id,
        'user_id' => $user->id,
        'prompt' => 'A glowing lantern in a moonlit forest',
        'size' => '1024x1024',
        'quality' => 'high',
        'status' => 'queued',
        // Prompt is 38 characters: 10 estimated text tokens (>= 9 at -15%),
        // 1 reference image (765 tokens), high 1024x1024 output (4300),
        // reasoning 1000-8000. User pays provider cost + 25%.
        'estimated_min_nd' => 240243750,
        'estimated_max_nd' => 349637500,
    ]);

    expect($generation->reference_images)->toBe(['https://example.com/reference.png']);
    Queue::assertPushedTimes(GenerateImage::class, 1);
    Queue::assertPushed(fn (GenerateImage $job): bool => $job->generation->user_id === $user->id);
});

test('image generation blocks users who cannot afford the estimate', function (): void {
    $user = User::factory()->withBalance(1000000)->create(); // $0.001, far below the estimate.
    config()->set('prism.providers.pixelforge.api_key', 'test-key');
    fakePixelforgePricing();
    Queue::fake();

    $this->actingAs($user)
        ->from('/dashboard')
        ->post('/dashboard/images', validImagePayload())
        ->assertRedirect('/dashboard')
        ->assertInvalid('balance');

    expect(Generation::count())->toBe(0);
    Queue::assertNothingPushed();
});

test('image generation fails gracefully when pricing is unavailable', function (): void {
    $user = User::factory()->withBalance()->create();
    config()->set('prism.providers.pixelforge.api_key', 'test-key');
    Http::fake([
        config('prism.providers.pixelforge.url').'/pricing' => Http::response([], 500),
    ]);

    $this->actingAs($user)
        ->from('/dashboard')
        ->post('/dashboard/images', validImagePayload())
        ->assertRedirect('/dashboard')
        ->assertSessionHas(
            'error',
            'Pixelforge pricing is temporarily unavailable. Please try again shortly.',
        );

    expect(Generation::count())->toBe(0);
});

test('dashboard lists generations with balances and pricing props', function (): void {
    $user = User::factory()->withBalance()->create();
    $ownGeneration = Generation::factory()->succeeded()->for($user)->create();
    Generation::factory()->succeeded()->create();
    fakePixelforgePricing();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('generations', 1)
            ->where('generations.0.id', $ownGeneration->id)
            ->where('generations.0.status', 'succeeded')
            ->has('generations.0.user_cost_display')
            ->has('generations.0.estimated_display')
            ->where('is_admin', false)
            ->has('pricing'));
});

test('unconfigured pricing shows the dashboard with an error flag', function (): void {
    $user = User::factory()->withBalance()->create();
    Http::fake([
        config('prism.providers.pixelforge.url').'/pricing' => Http::response([], 500),
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('pricing_error', true)
            ->where('pricing', null));
});

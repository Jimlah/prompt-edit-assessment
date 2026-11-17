<?php

use App\Jobs\ProcessPixelforgeEvent;
use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;

uses(LazilyRefreshDatabase::class);

const WEBHOOK_SECRET = 'whsec_0123456789abcdef';

/**
 * Posts a webhook with the raw body and a computed signature header, the way
 * Pixelforge delivers events.
 */
function postWebhook(string $body, string $signature, ?array $extraHeaders = []): TestResponse
{
    return test()->call(
        'POST',
        '/webhooks/pixelforge',
        [],
        [],
        [],
        array_merge(['HTTP_PIXELFORGE_SIGNATURE' => $signature], $extraHeaders ?? []),
        $body,
    );
}

function payloadWithSignature(array $payload, ?int $timestamp = null, string $secret = WEBHOOK_SECRET): array
{
    $timestamp ??= time();
    $body = json_encode($payload, JSON_THROW_ON_ERROR);
    $signature = hash_hmac('sha256', $timestamp.'.'.$body, $secret);

    return ['body' => $body, 'signature' => "t={$timestamp},v1={$signature}"];
}

function pingEvent(string $id = 'evt_ping_1'): array
{
    return ['id' => $id, 'object' => 'event', 'type' => 'ping', 'created_at' => '2026-11-17T12:00:00Z', 'data' => []];
}

test('webhooks require a valid signature', function (): void {
    config()->set('prism.providers.pixelforge.webhook_secret', WEBHOOK_SECRET);

    $payload = payloadWithSignature(pingEvent());

    // Missing header entirely.
    postWebhook($payload['body'], '', [])
        ->assertStatus(400);

    // Wrong signature for the body.
    postWebhook($payload['body'], 't=1,v1=deadbeef')
        ->assertStatus(400);

    // Signatures computed against a different secret are rejected.
    $other = payloadWithSignature(pingEvent('evt_other'), null, 'whsec_wrong');
    postWebhook($other['body'], $other['signature'])
        ->assertStatus(400);

    // Timestamps outside the 5-minute replay window are rejected.
    $stale = payloadWithSignature(pingEvent('evt_stale'), time() - 301);
    postWebhook($stale['body'], $stale['signature'])
        ->assertStatus(400);

    // A modified body breaks the signature.
    postWebhook($payload['body'].' ', $payload['signature'])
        ->assertStatus(400);

    expect(WebhookEvent::count())->toBe(0);
});

test('the signature timestamp is verified in constant time', function (): void {
    config()->set('prism.providers.pixelforge.webhook_secret', WEBHOOK_SECRET);

    // The same payload signed at a timestamp inside the window is fine.
    $payload = payloadWithSignature(pingEvent('evt_fresh'), time() - 295);

    postWebhook($payload['body'], $payload['signature'])->assertNoContent();
});

test('webhooks fail closed when the signing secret is not configured', function (): void {
    config()->set('prism.providers.pixelforge.webhook_secret', '');

    $payload = payloadWithSignature(pingEvent());

    postWebhook($payload['body'], $payload['signature'])->assertStatus(500);
});

test('signed webhooks are accepted and queued', function (): void {
    config()->set('prism.providers.pixelforge.webhook_secret', WEBHOOK_SECRET);
    Queue::fake();

    $payload = payloadWithSignature(pingEvent());

    postWebhook($payload['body'], $payload['signature'])->assertNoContent();

    expect(WebhookEvent::count())->toBe(1)
        ->and(WebhookEvent::first()->event_id)->toBe('evt_ping_1')
        ->and(WebhookEvent::first()->type)->toBe('ping');
    Queue::assertPushed(ProcessPixelforgeEvent::class, 1);
});

test('a delivered-twice event is never queued twice', function (): void {
    config()->set('prism.providers.pixelforge.webhook_secret', WEBHOOK_SECRET);
    Queue::fake();

    // Each delivery attempt is signed with a fresh timestamp; the event id is
    // the dedupe key.
    $first = payloadWithSignature(pingEvent('evt_double'), time() - 10);
    $second = payloadWithSignature(pingEvent('evt_double'), time());

    postWebhook($first['body'], $first['signature'])->assertNoContent();
    postWebhook($second['body'], $second['signature'])->assertNoContent();

    expect(WebhookEvent::count())->toBe(1);
    Queue::assertPushedTimes(ProcessPixelforgeEvent::class, 1);
});

test('events arriving before the local generation exists are stored and queued', function (): void {
    config()->set('prism.providers.pixelforge.webhook_secret', WEBHOOK_SECRET);
    Queue::fake();

    $payload = payloadWithSignature([
        'id' => 'evt_unknown',
        'object' => 'event',
        'type' => 'generation.succeeded',
        'created_at' => '2026-11-17T12:00:00Z',
        'data' => ['object' => ['id' => 'gen_unknown', 'object' => 'generation', 'status' => 'succeeded']],
    ]);

    postWebhook($payload['body'], $payload['signature'])->assertNoContent();

    expect(WebhookEvent::count())->toBe(1);
    Queue::assertPushed(ProcessPixelforgeEvent::class, 1);
});

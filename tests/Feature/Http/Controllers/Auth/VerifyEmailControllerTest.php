<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

uses(LazilyRefreshDatabase::class);

test('valid signed verification link marks the email as verified', function (): void {
    $user = User::factory()->unverified()->create();
    $url = URL::signedRoute('verification.verify', [
        'id' => $user->id,
        'hash' => sha1($user->getEmailForVerification()),
    ]);
    Event::fake([Verified::class]);

    $response = $this->actingAs($user)->get($url);

    $response->assertRedirect(route('dashboard'));
    $this->assertTrue($user->fresh()->hasVerifiedEmail());
    Event::assertDispatched(Verified::class, fn (Verified $event): bool => $event->user->is($user));
});

test('unsigned verification link is rejected', function (): void {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get("/email/verify/{$user->id}/invalid-hash");

    $response->assertForbidden();
    $this->assertFalse($user->fresh()->hasVerifiedEmail());
});

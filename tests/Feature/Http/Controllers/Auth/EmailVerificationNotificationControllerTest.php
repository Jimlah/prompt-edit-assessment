<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(LazilyRefreshDatabase::class);

test('unverified user can resend the verification notification', function (): void {
    $user = User::factory()->unverified()->create();
    Notification::fake();

    $response = $this->actingAs($user)
        ->from('/email/verify')
        ->post('/email/verification-notification');

    $response->assertRedirect('/email/verify')
        ->assertSessionHas('status', 'verification-link-sent');
    Notification::assertSentTo($user, VerifyEmail::class);
});

test('verified user is redirected without another notification', function (): void {
    $user = User::factory()->create();
    Notification::fake();

    $response = $this->actingAs($user)->post('/email/verification-notification');

    $response->assertRedirect(route('dashboard'));
    Notification::assertNothingSent();
});

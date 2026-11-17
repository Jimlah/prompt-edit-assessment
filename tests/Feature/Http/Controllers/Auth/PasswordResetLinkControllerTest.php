<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(LazilyRefreshDatabase::class);

test('forgot password page is available to guests', function (): void {
    $this->get('/forgot-password')->assertOk();
});

test('existing user receives a password reset notification', function (): void {
    $user = User::factory()->create(['email' => 'ada@example.com']);
    Notification::fake();

    $response = $this->from('/forgot-password')->post('/forgot-password', [
        'email' => 'ADA@EXAMPLE.COM',
    ]);

    $response->assertRedirect('/forgot-password')
        ->assertSessionHas('status', __('passwords.sent'));
    Notification::assertSentTo($user, ResetPassword::class);
});

test('unknown email returns a validation error without sending a notification', function (): void {
    Notification::fake();

    $response = $this->from('/forgot-password')->post('/forgot-password', [
        'email' => 'missing@example.com',
    ]);

    $response->assertRedirect('/forgot-password')->assertSessionHasErrors([
        'email' => __('passwords.user'),
    ]);
    Notification::assertNothingSent();
});

test('forgot password requires an email address', function (): void {
    $response = $this->from('/forgot-password')->post('/forgot-password', []);

    $response->assertRedirect('/forgot-password')->assertSessionHasErrors([
        'email' => 'The email field is required.',
    ]);
});

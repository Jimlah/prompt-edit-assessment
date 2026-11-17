<?php

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

uses(LazilyRefreshDatabase::class);

test('reset password page is available to guests', function (): void {
    $this->get('/reset-password/test-token?email=ada@example.com')->assertOk();
});

test('valid token resets the user password', function (): void {
    $user = User::factory()->create(['email' => 'ada@example.com']);
    $token = Password::createToken($user);
    Event::fake([PasswordReset::class]);

    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => 'ada@example.com',
        'password' => 'new-secret-password',
        'password_confirmation' => 'new-secret-password',
    ]);

    $response->assertRedirect(route('login'))
        ->assertSessionHas('status', __('passwords.reset'));
    $this->assertTrue(Hash::check('new-secret-password', $user->fresh()->password));
    Event::assertDispatched(PasswordReset::class, fn (PasswordReset $event): bool => $event->user->is($user));
});

test('invalid token does not change the user password', function (): void {
    $user = User::factory()->create(['email' => 'ada@example.com']);

    $response = $this->post('/reset-password', [
        'token' => 'invalid-token',
        'email' => 'ada@example.com',
        'password' => 'new-secret-password',
        'password_confirmation' => 'new-secret-password',
    ]);

    $response->assertSessionHasErrors([
        'email' => __('passwords.token'),
    ]);
    $this->assertTrue(Hash::check('password', $user->fresh()->password));
});

test('reset password requires token email and password', function (): void {
    $response = $this->post('/reset-password', []);

    $response->assertSessionHasErrors([
        'token' => 'The token field is required.',
        'email' => 'The email field is required.',
        'password' => 'The password field is required.',
    ]);
});

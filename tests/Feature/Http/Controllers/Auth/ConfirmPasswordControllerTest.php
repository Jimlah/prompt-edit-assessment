<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('confirm password page requires authentication', function (): void {
    $this->get('/confirm-password')->assertRedirect(route('login'));
});

test('authenticated user can view the confirm password page', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/confirm-password')->assertOk();
});

test('correct password marks the session as confirmed', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/confirm-password', [
        'password' => 'password',
    ]);

    $response->assertRedirect(route('dashboard'))
        ->assertSessionHas('auth.password_confirmed_at');
});

test('incorrect password returns a validation error', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/confirm-password', [
        'password' => 'incorrect-password',
    ]);

    $response->assertSessionHasErrors([
        'password' => 'The provided password does not match our records.',
    ])->assertSessionMissing('auth.password_confirmed_at');
});

<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('login page is available to guests', function (): void {
    $this->get('/login')->assertOk();
});

test('valid credentials authenticate the user', function (): void {
    $user = User::factory()->create(['email' => 'ada@example.com']);

    $response = $this->post('/login', [
        'email' => 'ADA@EXAMPLE.COM',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
});

test('invalid credentials return a validation error', function (): void {
    User::factory()->create(['email' => 'ada@example.com']);

    $response = $this->from('/login')->post('/login', [
        'email' => 'ada@example.com',
        'password' => 'incorrect-password',
    ]);

    $response->assertRedirect('/login')->assertSessionHasErrors([
        'email' => 'These credentials do not match our records.',
    ]);
    $this->assertGuest();
});

test('login requires an email and password', function (): void {
    $response = $this->from('/login')->post('/login', []);

    $response->assertRedirect('/login')->assertSessionHasErrors([
        'email' => 'The email field is required.',
        'password' => 'The password field is required.',
    ]);
    $this->assertGuest();
});

test('authenticated user can log out', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $response->assertRedirect(route('home'));
    $this->assertGuest();
});

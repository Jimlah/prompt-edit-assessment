<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

test('registration page is available to guests', function (): void {
    $this->get('/register')->assertOk();
});

test('valid registration creates user and authenticates them', function (): void {
    Event::fake([Registered::class]);

    $response = $this->post('/register', [
        'name' => ' Ada Lovelace ',
        'email' => 'ADA@EXAMPLE.COM',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ]);

    $user = User::where('email', 'ada@example.com')->firstOrFail();

    $response->assertRedirect(route('dashboard'));
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
    ]);
    $this->assertAuthenticatedAs($user);
    $this->assertTrue(Hash::check('secret-password', $user->password));
    Event::assertDispatched(Registered::class, fn (Registered $event): bool => $event->user->is($user));
});

test('registration requires name email and password', function (): void {
    $response = $this->from('/register')->post('/register', []);

    $response->assertRedirect('/register')->assertSessionHasErrors([
        'name' => 'The name field is required.',
        'email' => 'The email field is required.',
        'password' => 'The password field is required.',
    ]);
    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
});

test('registration rejects an existing email', function (): void {
    User::factory()->create(['email' => 'ada@example.com']);

    $response = $this->from('/register')->post('/register', [
        'name' => 'Another Ada',
        'email' => 'ada@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ]);

    $response->assertRedirect('/register')->assertSessionHasErrors([
        'email' => 'The email has already been taken.',
    ]);
    $this->assertGuest();
    $this->assertDatabaseCount('users', 1);
});

test('registration requires matching password confirmation', function (): void {
    $response = $this->from('/register')->post('/register', [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'different-password',
    ]);

    $response->assertRedirect('/register')->assertSessionHasErrors([
        'password' => 'The password field confirmation does not match.',
    ]);
    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
});

test('registration requires a password of at least eight characters', function (): void {
    $response = $this->from('/register')->post('/register', [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
    ]);

    $response->assertRedirect('/register')->assertSessionHasErrors([
        'password' => 'The password field must be at least 8 characters.',
    ]);
    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
});

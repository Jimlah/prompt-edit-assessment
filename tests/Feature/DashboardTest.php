<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('dashboard requires authentication', function (): void {
    $this->get('/dashboard')->assertRedirect(route('login'));
});

test('dashboard requires a verified email address', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get('/dashboard')->assertRedirect(route('verification.notice'));
});

test('verified user can view the dashboard', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/dashboard')->assertOk();
});

test('generate page requires authentication and verified email', function (): void {
    $this->get('/dashboard/generate')->assertRedirect(route('login'));

    $unverified = User::factory()->unverified()->create();
    $this->actingAs($unverified)->get('/dashboard/generate')->assertRedirect(route('verification.notice'));
});

test('verified user can view the generate page', function (): void {
    $user = User::factory()->withBalance()->create();
    fakePixelforgePricing();

    $this->actingAs($user)
        ->get('/dashboard/generate')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Generate')
            ->has('generations')
            ->has('pricing')
            ->has('balance_display'));
});

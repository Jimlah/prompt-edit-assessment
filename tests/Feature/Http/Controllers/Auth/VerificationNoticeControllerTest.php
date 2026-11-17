<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('verification notice requires authentication', function (): void {
    $this->get('/email/verify')->assertRedirect(route('login'));
});

test('unverified user can view the verification notice', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get('/email/verify')->assertOk();
});

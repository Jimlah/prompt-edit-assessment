<?php

use App\Models\BalanceTransaction;
use App\Models\Generation;
use App\Models\PixelforgeUsageDay;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;

uses(LazilyRefreshDatabase::class);

test('the report requires an admin', function (): void {
    $this->actingAs(User::factory()->create())
        ->get('/admin/report')
        ->assertForbidden();
});

test('the report lists every generation with users, deducted and actual costs, and every transaction', function (): void {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $failed = Generation::factory()
        ->for($user)
        ->failed()
        ->create(['created_at' => now()->subDay(), 'completed_at' => now()->subDay()]);
    $succeeded = Generation::factory()
        ->for($user)
        ->succeeded()
        ->create(['completed_at' => now()->subHours(2)]);
    Generation::factory()
        ->for($user)
        ->succeeded()
        ->create(['created_at' => now()->subDays(20), 'completed_at' => now()->subDays(20)]);

    $purchase = $user->balanceTransactions()->create([
        'type' => BalanceTransaction::TYPE_PURCHASE,
        'amount_nd' => 5000000000,
        'description' => 'Bought $5 of credits (test purchase).',
        'created_at' => now()->subHours(3),
    ]);
    $charge = $user->balanceTransactions()->create([
        'type' => BalanceTransaction::TYPE_CHARGE,
        'amount_nd' => -64087500,
        'generation_id' => $succeeded->id,
        'description' => 'Image generation.',
        'created_at' => now()->subHours(1),
    ]);

    Http::preventStrayRequests();

    $this->actingAs($admin)
        ->get('/admin/report')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Report')
            ->has('generations', 2)
            ->where('generations.0.id', $failed->id)
            ->where('generations.0.status', 'failed')
            ->where('generations.0.name', $user->name)
            ->where('generations.0.deducted_display', null)
            ->where('generations.0.actual_display', null)
            ->where('generations.1.id', $succeeded->id)
            ->where('generations.1.deducted_display', '$0.0641')
            ->where('generations.1.actual_display', '$0.0513')
            ->has('transactions', 2)
            ->where('transactions.0.id', $purchase->id)
            ->where('transactions.0.type', 'purchase')
            ->where('transactions.0.amount_display', '$5')
            ->where('transactions.1.id', $charge->id)
            ->where('transactions.1.type', 'charge')
            ->where('transactions.1.amount_nd', -64087500)
        );
});

test('the report reads the usage snapshot without calling the Pixelforge API', function (): void {
    $admin = User::factory()->admin()->create();

    PixelforgeUsageDay::create([
        'date' => now()->toDateString(),
        'generations' => 2,
        'input_text_tokens' => 71,
        'input_image_tokens' => 0,
        'output_tokens' => 10750,
        'reasoning_tokens' => 9354,
        'cost_nd' => 547280000,
        'fetched_at' => now(),
    ]);

    Http::preventStrayRequests();

    $this->actingAs($admin)
        ->get('/admin/report')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Report')
            ->where('reconciliation.rows.0.date', now()->toDateString())
            ->where('reconciliation.rows.0.our_generations', 0)
            ->where('reconciliation.rows.0.our_cost_display', '$0')
            ->where('reconciliation.rows.0.provider_generations', 2)
            ->where('reconciliation.rows.0.provider_cost_display', '$0.5473')
            ->where('reconciliation.rows.0.matches', false)
            ->has('reconciliation.rows.0.fetched_at')
        );
});

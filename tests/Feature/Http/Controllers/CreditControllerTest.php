<?php

use App\Models\BalanceTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

test('buying credits requires authentication', function (): void {
    $this->post('/dashboard/credits', ['amount' => 5])
        ->assertRedirect(route('login'));
});

test('buying credits adds the balance and logs a purchase', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/dashboard/credits', ['amount' => 10])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', '10 credits added to your balance.');

    $user->refresh();
    expect($user->credit_balance_nd)->toBe(10000000000)
        ->and($user->balanceTransactions()->where('type', 'purchase')->count())->toBe(1);

    $transaction = $user->balanceTransactions()->first();
    expect($transaction->amount_nd)->toBe(10000000000)
        ->and($transaction->description)->toBe('Bought $10 of credits (test purchase).');
});

test('credit purchases only accept the offered packs', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/dashboard')
        ->post('/dashboard/credits', ['amount' => 7])
        ->assertRedirect('/dashboard')
        ->assertInvalid('amount');

    $this->actingAs($user)
        ->post('/dashboard/credits', ['amount' => 'many'])
        ->assertRedirect('/dashboard')
        ->assertInvalid('amount');

    $this->actingAs($user)
        ->post('/dashboard/credits', [])
        ->assertRedirect('/dashboard')
        ->assertInvalid('amount');

    expect(DB::table('balance_transactions')->count())->toBe(0)
        ->and($user->refresh()->credit_balance_nd)->toBe(0);
});

test('charges and purchases keep the signed ledger consistent', function (): void {
    // Start at zero so the balance equals the transaction ledger exactly.
    $user = User::factory()->withBalance(0)->create();

    $user->balanceTransactions()->create([
        'type' => BalanceTransaction::TYPE_PURCHASE,
        'amount_nd' => 1000000000,
        'description' => 'Bought $1 of credits (test purchase).',
    ]);
    $user->increment('credit_balance_nd', 1000000000);
    $user->balanceTransactions()->create([
        'type' => BalanceTransaction::TYPE_CHARGE,
        'amount_nd' => -240243750,
        'description' => 'Image generation.',
    ]);
    $user->decrement('credit_balance_nd', 240243750);

    $user->refresh();

    expect($user->credit_balance_nd)->toBe($user->balanceTransactions()->sum('amount_nd'))
        ->and($user->credit_balance_nd)->toBe(759756250);
});

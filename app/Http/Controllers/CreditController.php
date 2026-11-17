<?php

namespace App\Http\Controllers;

use App\Models\BalanceTransaction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Fake purchases: there are no real payments, credits are simply US dollars.
 */
final class CreditController extends Controller
{
    /** Fake credit packs in whole dollars. */
    private const PACKS = [5, 10, 20];

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $validated = $request->validate([
            'amount' => ['required', 'integer', Rule::in(self::PACKS)],
        ]);

        $amountNd = (int) $validated['amount'] * 1000000000;

        DB::transaction(function () use ($user, $amountNd, $validated): void {
            /** @var User $user */
            $user->balanceTransactions()->create([
                'type' => BalanceTransaction::TYPE_PURCHASE,
                'amount_nd' => $amountNd,
                'description' => 'Bought $'.$validated['amount'].' of credits (test purchase).',
            ]);

            $user->increment('credit_balance_nd', $amountNd);
        });

        return redirect()
            ->route('dashboard')
            ->with('status', $validated['amount'].' credits added to your balance.');
    }
}

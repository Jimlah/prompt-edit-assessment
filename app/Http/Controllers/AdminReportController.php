<?php

namespace App\Http\Controllers;

use App\Models\BalanceTransaction;
use App\Models\Generation;
use App\Models\User;
use App\Services\UsageDaySync;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The operators' view: credit spend (what users were charged), provider costs
 * (what Pixelforge charged us) and profit, per day and per user, plus the full
 * ledger of generations and balance transactions. Everything is read from the
 * database — what actually happened in this app. The only provider-derived
 * section reconciles against the synced daily usage snapshot; the report
 * never calls the provider itself.
 */
final class AdminReportController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->is_admin) {
            abort(403);
        }

        $validated = $request->validate([
            'start' => ['sometimes', 'date_format:Y-m-d'],
            'end' => ['sometimes', 'date_format:Y-m-d'],
        ]);

        $end = isset($validated['end'])
            ? Carbon::createFromFormat('Y-m-d', $validated['end'], 'UTC')->endOfDay()
            : Carbon::now('UTC')->endOfDay();
        $start = isset($validated['start'])
            ? Carbon::createFromFormat('Y-m-d', $validated['start'], 'UTC')->startOfDay()
            : $end->copy()->subDays(13)->startOfDay();

        if ($start->gt($end)) {
            throw ValidationException::withMessages(['start' => 'The start date must be before the end date.']);
        }

        if ($start->diffInDays($end) > 92) {
            throw ValidationException::withMessages(['start' => 'The report can cover at most 92 days.']);
        }

        // Generations are billed on the UTC day they complete — the same rule
        // Pixelforge applies, so the two views can be compared day by day.
        $completed = $this->completedGenerations($start, $end);

        $days = (clone $completed)
            ->selectRaw('DATE(completed_at) as date, COUNT(*) as generations, COALESCE(SUM(provider_cost_nd), 0) as provider_nd, COALESCE(SUM(user_cost_nd), 0) as user_nd')
            ->groupByRaw('DATE(completed_at)')
            ->orderByRaw('DATE(completed_at)')
            ->get();

        $byUser = (clone $completed)
            ->selectRaw('MIN(completed_at) as generations, user_id, COUNT(*) as generation_count, COALESCE(SUM(provider_cost_nd), 0) as provider_nd, COALESCE(SUM(user_cost_nd), 0) as user_nd')
            ->groupBy('user_id')
            ->orderByDesc('user_nd')
            ->get();

        // The ledger: every generation requested in the range at any status,
        // and every balance transaction — what actually happened, read live.
        $generations = Generation::query()
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at')
            ->get();

        $transactions = BalanceTransaction::query()
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at')
            ->get();

        $users = User::query()
            ->whereIn('id', $byUser->pluck('user_id')
                ->merge($generations->pluck('user_id'))
                ->merge($transactions->pluck('user_id'))
                ->unique())
            ->get()
            ->keyBy('id');

        return Inertia::render('Admin/Report', [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'days' => $days->map(fn (Generation $day): array => $this->dayRow($day))->all(),
            'users' => $byUser->map(fn (Generation $row): array => $this->userRow($row, $users))->all(),
            'generations' => $generations->map(fn (Generation $generation): array => $this->generationRow($generation, $users))->all(),
            'transactions' => $transactions->map(fn (BalanceTransaction $transaction): array => $this->transactionRow($transaction, $users))->all(),
            'reconciliation' => $this->reconcile($start, $end),
        ]);
    }

    private function completedGenerations(Carbon $start, Carbon $end): Builder
    {
        return Generation::query()
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$start, $end]);
    }

    /**
     * @param  Generation  $day  A row from the daily aggregate query.
     * @return array<string, mixed>
     */
    private function dayRow(Generation $day): array
    {
        $providerNd = (int) $day->provider_nd;
        $userNd = (int) $day->user_nd;

        return [
            'date' => (string) $day->date,
            'generations' => (int) $day->generations,
            'provider_display' => Money::toDisplay($providerNd),
            'spend_display' => Money::toDisplay($userNd),
            'profit_display' => Money::toDisplay($userNd - $providerNd),
        ];
    }

    /**
     * @param  Generation  $row  A row from the per-user aggregate query.
     * @param  Collection<int, User>  $users
     * @return array<string, mixed>
     */
    private function userRow(Generation $row, Collection $users): array
    {
        /** @var User|null $user */
        $user = $users->get($row->user_id);
        $providerNd = (int) $row->provider_nd;
        $userNd = (int) $row->user_nd;

        return [
            'name' => $user?->name ?? 'Deleted user',
            'email' => $user?->email ?? '—',
            'generations' => (int) $row->generation_count,
            'provider_display' => Money::toDisplay($providerNd),
            'spend_display' => Money::toDisplay($userNd),
            'profit_display' => Money::toDisplay($userNd - $providerNd),
        ];
    }

    /**
     * One ledger row: the generation as it happened, with what the user was
     * deducted and what Pixelforge actually cost, whenever both are recorded.
     *
     * @param  Collection<int, User>  $users
     * @return array<string, mixed>
     */
    private function generationRow(Generation $generation, Collection $users): array
    {
        /** @var User|null $user */
        $user = $users->get($generation->user_id);

        return [
            'id' => $generation->id,
            'datetime' => $generation->created_at?->utc()->format('Y-m-d H:i'),
            'name' => $user?->name ?? 'Deleted user',
            'email' => $user?->email ?? '—',
            'prompt' => Str::limit($generation->prompt, 60),
            'provider_id' => $generation->provider_id,
            'status' => $generation->status->value,
            'deducted_display' => $generation->user_cost_nd !== null ? Money::toDisplay((int) $generation->user_cost_nd) : null,
            'actual_display' => $generation->provider_cost_nd !== null ? Money::toDisplay((int) $generation->provider_cost_nd) : null,
        ];
    }

    /**
     * One ledger row from the balance transactions: purchases, generation
     * charges and refunds, with the signed amount as stored.
     *
     * @param  Collection<int, User>  $users
     * @return array<string, mixed>
     */
    private function transactionRow(BalanceTransaction $transaction, Collection $users): array
    {
        /** @var User|null $user */
        $user = $users->get($transaction->user_id);

        return [
            'id' => $transaction->id,
            'datetime' => $transaction->created_at?->utc()->format('Y-m-d H:i'),
            'name' => $user?->name ?? 'Deleted user',
            'email' => $user?->email ?? '—',
            'type' => $transaction->type,
            'amount_nd' => $transaction->amount_nd,
            'amount_display' => Money::toDisplay($transaction->amount_nd),
            'description' => $transaction->description,
            'generation_id' => $transaction->generation_id,
        ];
    }

    /**
     * Compare what we recorded per UTC day with the usage snapshot the daily
     * sync keeps in the database. The report never calls the provider: rows
     * carry fetched_at so a lagging snapshot is visible. The point of the
     * section is to surface divergence — generations we recorded but
     * Pixelforge has not reported, or billed generations we have no row for.
     *
     * @return array<string, mixed>
     */
    private function reconcile(Carbon $start, Carbon $end): array
    {
        $ours = Generation::query()
            ->whereNotNull('completed_at')
            ->whereNotNull('provider_cost_nd')
            ->whereBetween('completed_at', [$start, $end])
            ->selectRaw('DATE(completed_at) as date, COUNT(*) as generations, COALESCE(SUM(provider_cost_nd), 0) as provider_nd')
            ->groupByRaw('DATE(completed_at)')
            ->get()
            ->keyBy('date');

        $rows = [];

        foreach (app(UsageDaySync::class)->days($start->toDateString(), $end->toDateString()) as $day) {
            $oursRow = $ours[$day->date] ?? null;
            $ourNd = (int) ($oursRow?->provider_nd ?? 0);

            $rows[] = [
                'date' => $day->date,
                'our_generations' => $oursRow?->generations ?? 0,
                'provider_generations' => $day->generations,
                'our_cost_display' => Money::toDisplay($ourNd),
                'provider_cost_display' => Money::toDisplay($day->cost_nd),
                'fetched_at' => $day->fetched_at?->toISOString(),
                'matches' => $ourNd === $day->cost_nd,
            ];
        }

        return [
            'rows' => $rows,
        ];
    }
}

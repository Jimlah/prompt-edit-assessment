<?php

namespace App\Http\Controllers;

use App\Models\Generation;
use App\Services\UsageDaySync;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use stdClass;

/**
 * The operators' view: credit spend (what users were charged), provider costs
 * (what Pixelforge charged us) and profit, per day and per user — reconciled
 * against Pixelforge's own records from GET /v1/usage.
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

        $days = $completed->copy()
            ->selectRaw('DATE(completed_at) as date, COUNT(*) as generations, COALESCE(SUM(provider_cost_nd), 0) as provider_nd, COALESCE(SUM(user_cost_nd), 0) as user_nd')
            ->groupByRaw('DATE(completed_at)')
            ->orderByRaw('DATE(completed_at)')
            ->get();

        $byUser = $completed->copy()
            ->selectRaw('MIN(completed_at) as generations, user_id, COUNT(*) as generation_count, COALESCE(SUM(provider_cost_nd), 0) as provider_nd, COALESCE(SUM(user_cost_nd), 0) as user_nd')
            ->groupBy('user_id')
            ->orderByDesc('user_nd')
            ->get();

        $users = User::query()->whereIn('id', $byUser->pluck('user_id'))->get()->keyBy('id');

        return Inertia::render('Admin/Report', [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'days' => $days->map(fn (stdClass $day): array => $this->dayRow($day))->all(),
            'users' => $byUser->map(fn (stdClass $row): array => $this->userRow($row, $users))->all(),
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
     * @param  stdClass  $day  A row from the daily aggregate query.
     * @return array<string, mixed>
     */
    private function dayRow(stdClass $day): array
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
     * @param  stdClass  $row  A row from the per-user aggregate query.
     * @param  Collection<int, User>  $users
     * @return array<string, mixed>
     */
    private function userRow(stdClass $row, Collection $users): array
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
     * Compare what we recorded per UTC day with what Pixelforge billed this
     * key. Usage is synced into the database first, so the report reads its
     * numbers from there; if the provider is unreachable, previously synced
     * days still reconcile (flagged as stale).
     *
     * @return array<string, mixed>|null
     */
    private function reconcile(Carbon $start, Carbon $end): ?array
    {
        $fresh = app(UsageDaySync::class)->sync($start->toDateString(), $end->toDateString());

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
            'fresh' => $fresh,
            'total_their_generations' => (int) $ours->sum('generations'),
        ];
    }
}

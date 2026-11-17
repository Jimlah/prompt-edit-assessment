<?php

namespace App\Services;

use App\Enums\GenerationStatus;
use App\Models\BalanceTransaction;
use App\Models\Generation;
use App\Models\User;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * The single writer of Pixelforge-driven state transitions.
 *
 * Webhooks, the generation job and the reconciliation command all funnel
 * through here, so events arriving twice or out of order converge on the same
 * state: a generation may only move forward, and its costs are recorded at
 * most once. The guarded updates are atomic at the database level, so racing
 * writers cannot double-charge.
 */
final class GenerationSync
{
    public function __construct(
        private readonly PixelforgePricing $pricing,
    ) {}

    /**
     * A generation left the queue at the provider.
     */
    public function markProcessing(Generation $generation, ?string $providerId = null): void
    {
        Generation::query()
            ->whereKey($generation->getKey())
            ->whereIn('status', GenerationStatus::values(GenerationStatus::Queued))
            ->update(array_filter([
                'status' => GenerationStatus::Processing,
                'provider_id' => $providerId ?? $generation->provider_id,
            ]));
    }

    /**
     * A generation succeeded. Records the usage and the exact provider and user
     * costs (provider cost + 25% markup) and charges the user's balance. Must
     * only take effect once per generation — a second call is a no-op.
     *
     * @param  array<string, mixed>  $providerGeneration  The provider generation object at the moment of success.
     */
    public function markSucceeded(Generation $generation, array $providerGeneration): bool
    {
        $attributes = $this->successAttributes($generation, $providerGeneration);

        $recorded = Generation::query()
            ->whereKey($generation->getKey())
            ->where('status', '!=', GenerationStatus::Succeeded)
            ->update($attributes);

        if (! $recorded) {
            return false;
        }

        $userCost = (int) $attributes['user_cost_nd'];

        if ($userCost > 0) {
            $this->chargeUser($generation, $userCost);
        }

        return true;
    }

    /**
     * A generation failed, either at the provider or because we gave up
     * submitting it. Failed generations cost nothing.
     */
    public function markFailed(Generation $generation, string $code, string $message): void
    {
        Generation::query()
            ->whereKey($generation->getKey())
            ->whereNotIn('status', GenerationStatus::values(GenerationStatus::Succeeded, GenerationStatus::Failed))
            ->update([
                'status' => GenerationStatus::Failed,
                'error' => json_encode(['code' => $code, 'message' => $message]),
                'completed_at' => now(),
            ]);
    }

    /**
     * @param  array<string, mixed>  $providerGeneration
     * @return array<string, mixed>
     */
    private function successAttributes(Generation $generation, array $providerGeneration): array
    {
        $usage = is_array($providerGeneration['usage'] ?? null) ? $providerGeneration['usage'] : null;

        if ($usage === null) {
            throw new \InvalidArgumentException('A succeeded generation must report token usage.');
        }

        $rates = $this->ratesFor($generation, $providerGeneration);
        $providerCost = $this->cost($usage, $rates);
        $userCost = Money::withMarkup($providerCost);

        return [
            'status' => 'succeeded',
            'provider_id' => is_string($providerGeneration['id'] ?? null) ? (string) $providerGeneration['id'] : $generation->provider_id,
            'image_url' => is_string($providerGeneration['image_url'] ?? null) ? (string) $providerGeneration['image_url'] : $generation->image_url,
            'image_url_expires_at' => $this->nullableDate($providerGeneration['image_url_expires_at'] ?? null),
            'usage' => json_encode($usage),
            'rates' => json_encode($rates),
            'provider_cost_nd' => $providerCost,
            'user_cost_nd' => $userCost,
            'error' => null,
            'completed_at' => $this->nullableDate($providerGeneration['completed_at'] ?? null) ?? now(),
        ];
    }

    /**
     * Exact Pixelforge cost for the token usage. When the rates recorded on
     * the generation are known (they always are, once a create succeeded) the
     * cost needs no network at all; only when no rates are recorded do we fall
     * back to the current published pricing sheet.
     *
     * @param  array<string, int>  $usage
     * @param  array<string, string>  $rates
     */
    private function cost(array $usage, array $rates): int
    {
        if ($rates !== []) {
            return PixelforgePriceSheet::costForUsageWithRateMap($usage, $rates);
        }

        return $this->pricing->sheet()->costForUsage($usage);
    }

    /**
     * Rates are fixed when the generation is created. Prefer the rates echoed
     * by the provider or the event; fall back to what we stored at submission.
     *
     * @param  array<string, mixed>  $providerGeneration
     * @return array<string, string>
     */
    private function ratesFor(Generation $generation, array $providerGeneration): array
    {
        $rates = [];

        foreach (PixelforgePriceSheet::TOKEN_CLASSES as $class) {
            $rate = $providerGeneration['rates'][$class] ?? null;

            if (is_string($rate) && $rate !== '') {
                $rates[$class] = $rate;
            }
        }

        if ($rates !== []) {
            return $rates;
        }

        $stored = $generation->rates;

        return is_array($stored) && $stored !== [] ? $stored : [];
    }

    private function chargeUser(Generation $generation, int $userCostNanodollars): void
    {
        DB::transaction(function () use ($generation, $userCostNanodollars): void {
            /** @var User|null $user */
            $user = User::query()->lockForUpdate()->find($generation->user_id);

            if ($user === null) {
                return;
            }

            $alreadyCharged = $user->balanceTransactions()
                ->where('generation_id', $generation->getKey())
                ->where('type', 'charge')
                ->exists();

            if ($alreadyCharged) {
                return;
            }

            $user->balanceTransactions()->create([
                'generation_id' => $generation->getKey(),
                'type' => BalanceTransaction::TYPE_CHARGE,
                'amount_nd' => -$userCostNanodollars,
                'description' => 'Image generation. Pixelforge billed '.$this->dollars($userCostNanodollars).' including the 25% markup.',
            ]);

            $user->decrement('credit_balance_nd', $userCostNanodollars);
        });
    }

    private function nullableDate(mixed $value): ?CarbonInterface
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    private function dollars(int $nanodollars): string
    {
        return '$'.rtrim(rtrim(Money::toString($nanodollars), '0'), '.');
    }
}

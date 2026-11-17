<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Brick\Money\Context\CustomContext;
use Brick\Money\Money as BrickMoney;

/**
 * Exact money as integer nanodollars (1 nd = $0.000000001), matching
 * Pixelforge's nine-decimal costs. The arithmetic itself is delegated to
 * brick/money, so rounding and conversions are never hand-rolled here.
 *
 * Nanodollar integers remain the storage and wire format: they are exact,
 * JSON-safe and free of floating point across the whole app.
 */
final class Money
{
    public const SCALE = 9;

    private const SCALE_FACTOR = '1000000000';

    /** The markup the app charges users on top of Pixelforge's cost (25%). */
    public const MARKUP_NUMERATOR = 125;

    public const MARKUP_DENOMINATOR = 100;

    private static function context(): CustomContext
    {
        return new CustomContext(self::SCALE);
    }

    /**
     * Parse an exact decimal dollar string, e.g. "5.00" or "0.046125000",
     * into integer nanodollars.
     */
    public static function fromString(string $amount): int
    {
        $amount = trim($amount);

        if ($amount === '' || preg_match('/^-?\d+(\.\d+)?$/', $amount) !== 1) {
            throw new \InvalidArgumentException("Invalid decimal amount: {$amount}");
        }

        $money = BrickMoney::of($amount, 'USD', self::context(), RoundingMode::Unnecessary);

        return self::toNano($money);
    }

    /**
     * Integer nanodollars as an exact decimal dollar string, e.g. 46125000 -> "0.046125".
     */
    public static function toString(int $nanodollars): string
    {
        return (string) BigDecimal::ofUnscaledValue($nanodollars, self::SCALE)->strippedOfTrailingZeros();
    }

    /**
     * A display string for users, rounded half-up to at most 4 decimals, e.g. 47312000 -> "$0.0473".
     */
    public static function toDisplay(int $nanodollars): string
    {
        $display = BigDecimal::ofUnscaledValue($nanodollars, self::SCALE)
            ->toScale(4, RoundingMode::HalfUp)
            ->strippedOfTrailingZeros();

        return '$'.str_replace('-0', '-', (string) $display);
    }

    /**
     * Apply the 25% markup: what the user pays for a given provider cost.
     * Rounded half-up at the nanodollar level when the split is not exact.
     */
    public static function withMarkup(int $providerNanodollars): int
    {
        return self::toNano(
            self::ofNano($providerNanodollars)
                ->multipliedBy((string) self::MARKUP_NUMERATOR, RoundingMode::Unnecessary)
                ->dividedBy((string) self::MARKUP_DENOMINATOR, RoundingMode::HalfUp),
        );
    }

    /**
     * Reverse the 25% markup: the provider cost implied by a user price.
     */
    public static function withoutMarkup(int $userNanodollars): int
    {
        return self::toNano(
            self::ofNano($userNanodollars)
                ->multipliedBy((string) self::MARKUP_DENOMINATOR, RoundingMode::Unnecessary)
                ->dividedBy((string) self::MARKUP_NUMERATOR, RoundingMode::HalfUp),
        );
    }

    /**
     * Cost in nanodollars for a token count at a rate given in nanodollars
     * per 1M tokens: cost = tokens × rate ÷ 1 000 000, rounded half-up.
     */
    public static function tokensCost(int $tokens, int $rateNanoPerMillion): int
    {
        if ($tokens === 0 || $rateNanoPerMillion === 0) {
            return 0;
        }

        return self::toNano(
            self::ofNano($rateNanoPerMillion)
                ->multipliedBy((string) $tokens, RoundingMode::Unnecessary)
                ->dividedBy('1000000', RoundingMode::HalfUp),
        );
    }

    public static function add(int ...$nanodollars): int
    {
        return array_sum($nanodollars);
    }

    /**
     * The same amount as an in-memory brick/money value.
     */
    public static function asMoney(int $nanodollars): BrickMoney
    {
        return self::ofNano($nanodollars);
    }

    private static function ofNano(int $nanodollars): BrickMoney
    {
        return BrickMoney::of(BigDecimal::ofUnscaledValue($nanodollars, self::SCALE), 'USD', self::context());
    }

    private static function toNano(BrickMoney $money): int
    {
        return $money->getAmount()
            ->multipliedBy(self::SCALE_FACTOR)
            ->toScale(0, RoundingMode::HalfUp)
            ->toInt();
    }
}

<?php

use App\Support\Money;
use Brick\Math\Exception\RoundingNecessaryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

// Money is pure arithmetic; the database is only touched because Pest's
// Feature suite is the registered test root for this project.
uses(LazilyRefreshDatabase::class);

test('decimal dollar strings round-trip into exact nanodollars', function (): void {
    expect(Money::fromString('5.00'))->toBe(5000000000)
        ->and(Money::fromString('0.046125000'))->toBe(46125000)
        ->and(Money::fromString('0.169080000'))->toBe(169080000)
        ->and(Money::fromString('12.50'))->toBe(12500000000)
        ->and(Money::toString(Money::fromString('5.00')))->toBe('5')
        ->and(Money::toString(Money::fromString('0.046125000')))->toBe('0.046125')
        ->and(Money::toString(0))->toBe('0');
});

test('parsing rejects malformed amounts rather than guessing', function (): void {
    expect(fn (): int => Money::fromString(''))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn (): int => Money::fromString('0.04,125'))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn (): int => Money::fromString('abc'))
        ->toThrow(InvalidArgumentException::class);
});

test('the 25 percent markup is exact', function (): void {
    expect(Money::withMarkup(192195000))->toBe(240243750)
        ->and(Money::withMarkup(54815000))->toBe(68518750)
        ->and(Money::withMarkup(0))->toBe(0)
        ->and(Money::withoutMarkup(240243750))->toBe(192195000)
        ->and(Money::withoutMarkup(Money::withMarkup(1234)))->toBeLessThanOrEqual(1234);
});

test('token costs are exact against the published rates', function (): void {
    // Rates in nanodollars per 1M tokens: text $5, image $10, output $40, reasoning $12.50.
    expect(Money::tokensCost(4300, 40000000000))->toBe(172000000) // output high 1024
        ->and(Money::tokensCost(6450, 40000000000))->toBe(258000000) // output high wide
        ->and(Money::tokensCost(765, 10000000000))->toBe(7650000) // one reference image
        ->and(Money::tokensCost(3184, 12500000000))->toBe(39800000) // reasoning tokens
        ->and(Money::tokensCost(115, 5000000000))->toBe(575000) // input text tokens
        ->and(Money::tokensCost(0, 40000000000))->toBe(0)
        // The worked example from the Pixelforge docs adds up to $0.373875.
        ->and(Money::add(
            Money::tokensCost(115, 5000000000),
            Money::tokensCost(1530, 10000000000),
            Money::tokensCost(6450, 40000000000),
            Money::tokensCost(8000, 12500000000),
        ))->toBe(373875000);
});

test('display strings round to four decimals half-up', function (): void {
    expect(Money::toDisplay(46125000))->toBe('$0.0461')
        ->and(Money::toDisplay(240243750))->toBe('$0.2402')
        ->and(Money::toDisplay(64087500))->toBe('$0.0641')
        ->and(Money::toDisplay(0))->toBe('$0')
        ->and(Money::toDisplay(1000000000))->toBe('$1')
        // 0.00575 → $0.0058 (round up)
        ->and(Money::toDisplay(5750000))->toBe('$0.0058')
        // 0.00574 → $0.0057 (round down)
        ->and(Money::toDisplay(5740000))->toBe('$0.0057');
});

test('parsing refuses more precision than the app supports', function (): void {
    expect(fn (): int => Money::fromString('0.0000000005'))
        ->toThrow(RoundingNecessaryException::class);
});

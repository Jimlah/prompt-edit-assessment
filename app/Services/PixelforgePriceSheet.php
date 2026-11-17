<?php

namespace App\Services;

use App\Support\Money;
use InvalidArgumentException;

/**
 * An immutable snapshot of Pixelforge's published pricing. All rates are kept
 * as integer nanodollars per 1M tokens so every cost computation is exact.
 *
 * @phpstan-type Usage array{input_text_tokens: int, input_image_tokens: int, output_tokens: int, reasoning_tokens: int, total_tokens: int}
 * @phpstan-type RateMap array<string, string>
 */
final readonly class PixelforgePriceSheet
{
    public const TOKEN_CLASSES = ['input_text_tokens', 'input_image_tokens', 'output_tokens', 'reasoning_tokens'];

    /**
     * @param  array<string, int>  $ratesNd  Rate per 1M tokens, in nanodollars, keyed by token class.
     * @param  array<string, array<string, int>>  $output  Exact output token counts: quality => size => count.
     * @param  array<string, array{min: int, max: int}>  $reasoning  Reasoning token ranges: quality => min/max.
     */
    public function __construct(
        public array $ratesNd,
        public int $charactersPerTextToken,
        public float $textTokenVariance,
        public int $perReferenceImage,
        public array $output,
        public array $reasoning,
    ) {}

    /**
     * Build the sheet from the GET /v1/pricing response body.
     *
     * @param  array<string, mixed>  $pricing
     */
    public static function fromApi(array $pricing): self
    {
        $rates = $pricing['rates'] ?? null;
        $tokens = $pricing['tokens'] ?? null;

        if (! is_array($rates) || ! is_array($tokens)) {
            throw new \RuntimeException('Pixelforge returned an unrecognised pricing payload.');
        }

        $ratesNd = [];

        foreach (self::TOKEN_CLASSES as $class) {
            $rate = is_string($rates[$class] ?? null) ? $rates[$class] : null;

            if ($rate === null) {
                throw new \RuntimeException("Pixelforge pricing is missing the {$class} rate.");
            }

            $ratesNd[$class] = Money::fromString($rate);
        }

        $output = self::nestedIntegerMap($tokens['output'] ?? null, 'output');
        $reasoning = [];

        foreach (is_array($tokens['reasoning'] ?? null) ? $tokens['reasoning'] : [] as $quality => $range) {
            if (! is_array($range) || ! isset($range['min'], $range['max'])
                || ! is_int($range['min']) || ! is_int($range['max'])) {
                throw new \RuntimeException("Pixelforge pricing has an invalid reasoning range for \"{$quality}\".");
            }

            $reasoning[$quality] = ['min' => $range['min'], 'max' => $range['max']];
        }

        if ($reasoning === []) {
            throw new \RuntimeException('Pixelforge pricing is missing reasoning token ranges.');
        }

        return new self(
            ratesNd: $ratesNd,
            charactersPerTextToken: self::positiveInteger($tokens['characters_per_text_token'] ?? null, 'characters_per_text_token'),
            textTokenVariance: self::variance($tokens['text_token_variance'] ?? null),
            perReferenceImage: self::positiveInteger($tokens['per_reference_image'] ?? null, 'per_reference_image'),
            output: $output,
            reasoning: $reasoning,
        );
    }

    /**
     * The Pixelforge provider cost, estimated, for one generation of the given
     * shape. Text and reasoning tokens are only known after completion, so an
     * exact range is returned: [minimum, maximum] in nanodollars.
     *
     * @return array{min_nd: int, max_nd: int}
     */
    public function estimate(string $prompt, string $quality, string $size, int $referenceImageCount): array
    {
        $charCount = mb_strlen($prompt);
        $textTokens = (int) ceil($charCount / max(1, $this->charactersPerTextToken));
        $textMin = max(0, (int) ceil($textTokens * (1 - $this->textTokenVariance)));
        $textMax = $textTokens + (int) ceil($textTokens * $this->textTokenVariance);

        $imageTokens = $this->perReferenceImage * max(0, $referenceImageCount);
        $outputTokens = $this->output[$quality][$size] ?? null;

        if ($outputTokens === null) {
            throw new InvalidArgumentException("No output token count for \"{$quality}\" at \"{$size}\".");
        }

        $reasoningRange = $this->reasoning[$quality] ?? null;

        if ($reasoningRange === null) {
            throw new InvalidArgumentException("No reasoning token range for \"{$quality}\".");
        }

        $min = Money::tokensCost($textMin, $this->ratesNd['input_text_tokens'])
            + Money::tokensCost($imageTokens, $this->ratesNd['input_image_tokens'])
            + Money::tokensCost($outputTokens, $this->ratesNd['output_tokens'])
            + Money::tokensCost($reasoningRange['min'], $this->ratesNd['reasoning_tokens']);

        $max = Money::tokensCost($textMax, $this->ratesNd['input_text_tokens'])
            + Money::tokensCost($imageTokens, $this->ratesNd['input_image_tokens'])
            + Money::tokensCost($outputTokens, $this->ratesNd['output_tokens'])
            + Money::tokensCost($reasoningRange['max'], $this->ratesNd['reasoning_tokens']);

        return ['min_nd' => $min, 'max_nd' => $max];
    }

    /**
     * The exact Pixelforge cost for completed token usage, at rates given as
     * the provider's decimal dollar strings. No published sheet is consulted,
     * so this path needs no network.
     *
     * @param  Usage  $usage
     * @param  RateMap  $rates
     */
    public static function costForUsageWithRateMap(array $usage, array $rates): int
    {
        $cost = 0;

        foreach (self::TOKEN_CLASSES as $class) {
            $tokens = $usage[$class] ?? 0;
            $rate = $rates[$class] ?? null;

            if ($tokens === 0) {
                continue;
            }

            if (! is_string($rate) || $rate === '') {
                throw new \RuntimeException("No recorded Pixelforge rate for token class \"{$class}\".");
            }

            $cost += Money::tokensCost($tokens, Money::fromString($rate));
        }

        return $cost;
    }

    /**
     * The exact Pixelforge cost for completed token usage. Rates recorded on the
     * generation (fixed when it was created) take precedence over current rates.
     *
     * @param  Usage  $usage
     * @param  RateMap|null  $rates
     */
    public function costForUsage(array $usage, ?array $rates = null): int
    {
        $cost = 0;

        foreach (self::TOKEN_CLASSES as $class) {
            $tokens = $usage[$class] ?? 0;

            if ($tokens === 0) {
                continue;
            }

            $rate = $rates[$class] ?? null;

            if (is_string($rate)) {
                $cost += Money::tokensCost($tokens, Money::fromString($rate));

                continue;
            }

            $cost += Money::tokensCost($tokens, $this->ratesNd[$class]);
        }

        return $cost;
    }

    /**
     * The pricing shape the frontend uses to estimate costs before submitting.
     *
     * @return array<string, mixed>
     */
    public function toFrontendArray(): array
    {
        return [
            'rates_nd' => $this->ratesNd,
            'markup' => ['numerator' => Money::MARKUP_NUMERATOR, 'denominator' => Money::MARKUP_DENOMINATOR],
            'chars_per_text_token' => $this->charactersPerTextToken,
            'text_token_variance' => $this->textTokenVariance,
            'per_reference_image' => $this->perReferenceImage,
            'output' => $this->output,
            'reasoning' => $this->reasoning,
        ];
    }

    private static function positiveInteger(mixed $value, string $field): int
    {
        if (! is_int($value) || $value < 1) {
            throw new \RuntimeException("Pixelforge pricing has an invalid {$field}.");
        }

        return $value;
    }

    private static function variance(mixed $value): float
    {
        if (! is_int($value) && ! is_float($value)) {
            throw new \RuntimeException('Pixelforge pricing has an invalid text_token_variance.');
        }

        $variance = (float) $value;

        return $variance >= 0 && $variance <= 1 ? $variance : 0.5;
    }

    /**
     * @return array<string, array<string, int>>
     */
    private static function nestedIntegerMap(mixed $value, string $field): array
    {
        $result = [];

        foreach (is_array($value) ? $value : [] as $quality => $sizes) {
            if (! is_array($sizes)) {
                throw new \RuntimeException("Pixelforge pricing has an invalid {$field} table.");
            }

            foreach ($sizes as $size => $tokens) {
                if (! is_string($size) || ! is_int($tokens) || $tokens < 0) {
                    throw new \RuntimeException("Pixelforge pricing has an invalid {$field} table.");
                }

                $result[$quality][$size] = $tokens;
            }
        }

        if ($result === []) {
            throw new \RuntimeException("Pixelforge pricing is missing the {$field} table.");
        }

        return $result;
    }
}

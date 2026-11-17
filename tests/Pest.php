<?php

use App\Services\PixelforgePricing;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Pest configuration
|--------------------------------------------------------------------------
*/

pest()->extend(TestCase::class)->in('Feature');

/**
 * Pixelforge's published price sheet, as GET /v1/pricing returns it.
 *
 * @return array<string, mixed>
 */
function pricingPayload(): array
{
    return [
        'object' => 'pricing',
        'currency' => 'usd',
        'unit' => 'per_1m_tokens',
        'rates' => [
            'input_text_tokens' => '5.00',
            'input_image_tokens' => '10.00',
            'output_tokens' => '40.00',
            'reasoning_tokens' => '12.50',
        ],
        'tokens' => [
            'characters_per_text_token' => 4,
            'text_token_variance' => 0.15,
            'per_reference_image' => 765,
            'output' => [
                'low' => ['1024x1024' => 280, '1536x1024' => 420, '1024x1536' => 420],
                'medium' => ['1024x1024' => 1100, '1536x1024' => 1650, '1024x1536' => 1650],
                'high' => ['1024x1024' => 4300, '1536x1024' => 6450, '1024x1536' => 6450],
            ],
            'reasoning' => [
                'low' => ['min' => 0, 'max' => 500],
                'medium' => ['min' => 200, 'max' => 2000],
                'high' => ['min' => 1000, 'max' => 8000],
            ],
        ],
    ];
}

/**
 * Point the Pixelforge client at a fakeable base url and serve the pricing
 * payload, so controller tests never reach the network.
 */
function fakePixelforgePricing(string $url = 'https://pixelforge.test'): void
{
    config()->set('prism.providers.pixelforge.url', $url.'/v1');
    app(PixelforgePricing::class)->forgetCache();
    Http::fake([
        $url.'/v1/pricing' => Http::response(pricingPayload()),
    ]);
}

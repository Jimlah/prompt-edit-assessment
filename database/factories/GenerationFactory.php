<?php

namespace Database\Factories;

use App\Enums\GenerationStatus;
use App\Models\Generation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Generation>
 */
class GenerationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider_id' => null,
            'prompt' => fake()->sentence(),
            'size' => '1024x1024',
            'quality' => 'high',
            'reference_images' => [],
            'status' => GenerationStatus::Queued,
            'image_url' => null,
            'error' => null,
            'usage' => null,
            'rates' => null,
            'completed_at' => null,
        ];
    }

    public function processing(): static
    {
        return $this->state(fn (): array => [
            'status' => GenerationStatus::Processing,
        ]);
    }

    public function succeeded(): static
    {
        return $this->state(fn (): array => [
            'status' => GenerationStatus::Succeeded,
            'provider_id' => 'gen_'.Str::lower(Str::random(20)),
            'image_url' => '/images/showcase/lantern.webp',
            'usage' => [
                'input_text_tokens' => 24,
                'input_image_tokens' => 0,
                'output_tokens' => 1100,
                'reasoning_tokens' => 500,
                'total_tokens' => 1624,
            ],
            'rates' => [
                'input_text_tokens' => '5.00',
                'input_image_tokens' => '10.00',
                'output_tokens' => '40.00',
                'reasoning_tokens' => '12.50',
            ],
            'provider_cost_nd' => 51270000,
            'user_cost_nd' => 64087500,
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => GenerationStatus::Failed,
            'error' => [
                'code' => 'generation_failed',
                'message' => 'The image could not be created.',
            ],
            'completed_at' => now(),
        ]);
    }
}

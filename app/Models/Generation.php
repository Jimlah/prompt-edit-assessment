<?php

namespace App\Models;

use App\Enums\GenerationStatus;
use App\Support\Money;
use Database\Factories\GenerationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property int $user_id
 * @property ?string $provider_id
 * @property string $prompt
 * @property string $size
 * @property string $quality
 * @property array<int, string>|null $reference_images
 * @property GenerationStatus $status
 * @property ?string $image_url
 * @property ?Carbon $image_url_expires_at
 * @property array<string, mixed>|null $error
 * @property ?array<string, int> $usage Pixelforge token usage, exact counts.
 * @property ?array<string, string> $rates Rate per 1M tokens when created, in dollar strings.
 * @property ?int $estimated_min_nd Estimated user cost (with markup), integer nanodollars.
 * @property ?int $estimated_max_nd Estimated user cost (with markup), integer nanodollars.
 * @property ?int $provider_cost_nd What Pixelforge charged, integer nanodollars.
 * @property ?int $user_cost_nd What the user was charged, integer nanodollars.
 * @property ?Carbon $completed_at
 * @property ?Carbon $created_at
 */
#[Fillable([
    'user_id', 'provider_id', 'prompt', 'size', 'quality', 'reference_images',
    'status', 'image_url', 'image_url_expires_at', 'error', 'usage', 'rates',
    'estimated_min_nd', 'estimated_max_nd', 'provider_cost_nd', 'user_cost_nd', 'completed_at',
])]
class Generation extends Model
{
    /** @use HasFactory<GenerationFactory> */
    use HasFactory, HasUuids;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<BalanceTransaction, $this>
     */
    public function balanceTransactions(): HasMany
    {
        return $this->hasMany(BalanceTransaction::class);
    }

    /**
     * The rows the dashboard renders: live status, the estimate the user saw,
     * the recorded actual costs, and the image itself.
     *
     * @return array<string, mixed>
     */
    public function toDashboard(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'prompt' => $this->prompt,
            'size' => $this->size,
            'quality' => $this->quality,
            'reference_images' => $this->reference_images ?? [],
            'image_url' => $this->image_url,
            'image_url_expires_at' => $this->image_url_expires_at?->toISOString(),
            'error' => $this->error,
            'usage' => $this->usage,
            'created_at' => $this->created_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'estimated_min_nd' => $this->estimated_min_nd !== null ? (string) $this->estimated_min_nd : null,
            'estimated_max_nd' => $this->estimated_max_nd !== null ? (string) $this->estimated_max_nd : null,
            'provider_cost_nd' => $this->provider_cost_nd !== null ? (string) $this->provider_cost_nd : null,
            'user_cost_nd' => $this->user_cost_nd !== null ? (string) $this->user_cost_nd : null,
            'estimated_display' => $this->estimateDisplay(),
            'user_cost_display' => $this->user_cost_nd !== null ? Money::toDisplay((int) $this->user_cost_nd) : null,
            'provider_cost_display' => $this->provider_cost_nd !== null ? Money::toDisplay((int) $this->provider_cost_nd) : null,
        ];
    }

    private function estimateDisplay(): ?string
    {
        if ($this->estimated_min_nd === null || $this->estimated_max_nd === null) {
            return null;
        }

        $min = Money::toDisplay((int) $this->estimated_min_nd);
        $max = Money::toDisplay((int) $this->estimated_max_nd);

        return $min === $max ? $min : "{$min} – {$max}";
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reference_images' => 'array',
            'error' => 'array',
            'usage' => 'array',
            'rates' => 'array',
            'status' => GenerationStatus::class,
            'completed_at' => 'datetime',
            'image_url_expires_at' => 'datetime',
        ];
    }
}

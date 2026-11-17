<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property int $user_id
 * @property ?string $generation_id
 * @property string $type purchase|charge|refund
 * @property int $amount_nd Signed: positive adds credits, negative removes them.
 * @property ?string $description
 * @property Carbon|null $created_at
 */
#[Fillable(['user_id', 'generation_id', 'type', 'amount_nd', 'description'])]
class BalanceTransaction extends Model
{
    public const TYPE_PURCHASE = 'purchase';

    public const TYPE_CHARGE = 'charge';

    public const TYPE_REFUND = 'refund';

    use HasUuids;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Generation, $this>
     */
    public function generation(): BelongsTo
    {
        return $this->belongsTo(Generation::class);
    }
}

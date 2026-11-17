<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'date', 'generations', 'input_text_tokens', 'input_image_tokens',
    'output_tokens', 'reasoning_tokens', 'cost_nd', 'fetched_at',
])]
class PixelforgeUsageDay extends Model {}

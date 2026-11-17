<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pixelforge_usage_days', function (Blueprint $table) {
            $table->id();
            $table->string('date', 10)->unique(); // YYYY-MM-DD, the UTC day the provider billed.
            $table->unsignedBigInteger('generations')->default(0);
            $table->unsignedBigInteger('input_text_tokens')->default(0);
            $table->unsignedBigInteger('input_image_tokens')->default(0);
            $table->unsignedBigInteger('output_tokens')->default(0);
            $table->unsignedBigInteger('reasoning_tokens')->default(0);
            $table->unsignedBigInteger('cost_nd'); // Exact provider cost, in nanodollars.
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pixelforge_usage_days');
    }
};

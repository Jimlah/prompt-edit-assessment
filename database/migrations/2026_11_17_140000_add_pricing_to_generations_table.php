<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generations', function (Blueprint $table) {
            $table->json('rates')->nullable()->after('usage');
            $table->unsignedBigInteger('estimated_min_nd')->nullable()->after('rates');
            $table->unsignedBigInteger('estimated_max_nd')->nullable()->after('estimated_min_nd');
            $table->unsignedBigInteger('provider_cost_nd')->nullable()->after('estimated_max_nd');
            $table->unsignedBigInteger('user_cost_nd')->nullable()->after('provider_cost_nd');
            $table->timestamp('completed_at')->nullable()->after('user_cost_nd');
            $table->timestamp('image_url_expires_at')->nullable()->after('completed_at');
            $table->index('provider_id');
        });
    }

    public function down(): void
    {
        Schema::table('generations', function (Blueprint $table) {
            $table->dropColumn([
                'rates', 'estimated_min_nd', 'estimated_max_nd',
                'provider_cost_nd', 'user_cost_nd', 'completed_at', 'image_url_expires_at',
            ]);
            $table->dropIndex(['provider_id']);
        });
    }
};

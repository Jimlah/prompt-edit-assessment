<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider_id')->nullable();
            $table->text('prompt');
            $table->string('size', 20);
            $table->string('quality', 20);
            $table->string('style', 80);
            $table->string('effect', 80);
            $table->string('status', 20)->default('queued')->index();
            $table->text('image_url')->nullable();
            $table->json('error')->nullable();
            $table->json('usage')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generations');
    }
};

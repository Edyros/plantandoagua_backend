<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planting_updates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('planting_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('observed_at');
            $table->text('notes')->nullable();
            $table->json('photo_uris')->nullable();
            $table->timestamps();

            $table->foreign('planting_id')->references('id')->on('plantings')->cascadeOnDelete();
            $table->index(['planting_id', 'observed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planting_updates');
    }
};

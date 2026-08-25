<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plantings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('species');
            $table->string('scientific_name')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamp('planted_at');
            $table->string('supplier_id')->nullable();
            $table->string('supplier_name')->nullable();
            $table->text('observations')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('location_name')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->json('photo_uris')->nullable();
            $table->timestamps();

            $table->index(['latitude', 'longitude']);
            $table->index('user_id');
            $table->index('planted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plantings');
    }
};

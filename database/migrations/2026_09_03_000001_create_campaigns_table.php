<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('total');
            $table->unsignedInteger('remaining');
            $table->string('visibility')->default('public')->index();
            $table->string('invite_code', 16)->nullable()->unique();
            $table->string('status')->default('pending_payment')->index();
            $table->uuid('payment_id')->nullable()->index();
            $table->timestamps();

            $table->foreign('payment_id')->references('id')->on('payments')->nullOnDelete();
            $table->index(['user_id', 'created_at']);
            $table->index(['visibility', 'status', 'remaining']);
        });

        Schema::create('campaign_user', function (Blueprint $table) {
            $table->id();
            $table->uuid('campaign_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->foreign('campaign_id')->references('id')->on('campaigns')->cascadeOnDelete();
            $table->unique(['campaign_id', 'user_id']);
        });

        Schema::table('plantings', function (Blueprint $table) {
            $table->uuid('campaign_id')->nullable()->after('user_id');
            $table->foreign('campaign_id')->references('id')->on('campaigns')->nullOnDelete();
            $table->index('campaign_id');
        });
    }

    public function down(): void
    {
        Schema::table('plantings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('campaign_id');
        });
        Schema::dropIfExists('campaign_user');
        Schema::dropIfExists('campaigns');
    }
};

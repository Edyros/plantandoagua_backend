<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('hebronpay');
            $table->string('provider_invoice_id')->nullable()->index();
            $table->string('identifier')->unique();
            $table->unsignedInteger('amount_cents');
            $table->string('currency', 3)->default('BRL');
            $table->string('payment_method');
            $table->string('status')->default('pending')->index();
            $table->string('description')->nullable();
            $table->text('pix_copy_paste')->nullable();
            $table->text('pix_qr_code')->nullable();
            $table->string('checkout_url', 1024)->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('provider_payload')->nullable();
            $table->json('last_webhook')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

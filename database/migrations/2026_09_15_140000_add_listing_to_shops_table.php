<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->timestamp('listing_paid_until')->nullable()->after('visible');
            $table->uuid('listing_payment_id')->nullable()->after('listing_paid_until');
            $table->uuid('listing_settled_payment_id')->nullable()->after('listing_payment_id');

            $table->foreign('listing_payment_id')
                ->references('id')
                ->on('payments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropConstrainedForeignId('listing_payment_id');
            $table->dropColumn(['listing_paid_until', 'listing_settled_payment_id']);
        });
    }
};

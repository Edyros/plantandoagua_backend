<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('appear_on_community_map')->default(true)->after('profile_complete');
            $table->boolean('public_profile')->default(true)->after('appear_on_community_map');
            $table->boolean('show_city_on_profile')->default(true)->after('public_profile');
            $table->string('pin_precision', 16)->default('exact')->after('show_city_on_profile');
            $table->unsignedTinyInteger('monthly_goal')->default(20)->after('pin_precision');
            $table->string('default_map_filter', 16)->default('mine')->after('monthly_goal');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'appear_on_community_map',
                'public_profile',
                'show_city_on_profile',
                'pin_precision',
                'monthly_goal',
                'default_map_filter',
            ]);
        });
    }
};

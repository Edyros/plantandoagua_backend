<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
            $table->string('phone')->nullable()->after('email');
            $table->string('cpf', 14)->nullable()->after('phone');
            $table->string('city')->nullable()->after('cpf');
            $table->string('state', 2)->nullable()->after('city');
            $table->string('avatar_url')->nullable()->after('state');
            $table->unsignedInteger('eco_points')->default(0)->after('avatar_url');
            $table->unsignedInteger('trees_planted')->default(0)->after('eco_points');
            $table->unsignedTinyInteger('profile_complete')->default(0)->after('trees_planted');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'uuid',
                'phone',
                'cpf',
                'city',
                'state',
                'avatar_url',
                'eco_points',
                'trees_planted',
                'profile_complete',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('cpf', 18)->nullable()->change();
            $table->string('website')->nullable()->after('state');
            $table->string('instagram', 120)->nullable()->after('website');
            $table->string('facebook')->nullable()->after('instagram');
            $table->string('linkedin')->nullable()->after('facebook');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['website', 'instagram', 'facebook', 'linkedin']);
            $table->string('cpf', 14)->nullable()->change();
        });
    }
};

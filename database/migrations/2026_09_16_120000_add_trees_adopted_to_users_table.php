<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('trees_adopted')->default(0)->after('trees_planted');
        });

        if (! Schema::hasTable('plantings')) {
            return;
        }

        $adopted = DB::table('plantings')
            ->select('user_id', DB::raw('SUM(quantity) as total'))
            ->where('kind', 'adopted')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $planted = DB::table('plantings')
            ->select('user_id', DB::raw('SUM(quantity) as total'))
            ->where(function ($query) {
                $query->whereNull('kind')->orWhere('kind', '!=', 'adopted');
            })
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        foreach ($planted->keys()->merge($adopted->keys())->unique() as $userId) {
            DB::table('users')->where('id', $userId)->update([
                'trees_planted' => (int) ($planted[$userId] ?? 0),
                'trees_adopted' => (int) ($adopted[$userId] ?? 0),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('trees_adopted');
        });
    }
};

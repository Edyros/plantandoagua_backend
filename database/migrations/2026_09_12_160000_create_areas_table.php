<?php

use App\Models\Area;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind')->default(Area::KIND_CAMPAIGN)->index();
            $table->string('name')->nullable();
            $table->json('vertices');
            $table->timestamps();
            $table->index(['user_id', 'kind']);
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->uuid('area_id')->nullable()->after('per_user_limit');
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->foreign('area_id')->references('id')->on('areas')->nullOnDelete();
        });

        if (Schema::hasColumn('campaigns', 'area')) {
            $campaigns = DB::table('campaigns')->whereNotNull('area')->get();
            foreach ($campaigns as $campaign) {
                $payload = is_string($campaign->area) ? json_decode($campaign->area, true) : $campaign->area;
                $vertices = is_array($payload['vertices'] ?? null) ? $payload['vertices'] : null;
                if (! is_array($vertices) || count($vertices) < 4) {
                    continue;
                }
                $areaId = (string) Str::uuid();
                $now = now();
                DB::table('areas')->insert([
                    'id' => $areaId,
                    'user_id' => $campaign->user_id,
                    'kind' => Area::KIND_CAMPAIGN,
                    'name' => $campaign->name,
                    'vertices' => json_encode(Area::normalizedVertices($vertices)),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                DB::table('campaigns')->where('id', $campaign->id)->update(['area_id' => $areaId]);
            }

            Schema::table('campaigns', function (Blueprint $table) {
                $table->dropColumn('area');
            });
        }
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('area_id');
        });
        Schema::dropIfExists('areas');
    }
};

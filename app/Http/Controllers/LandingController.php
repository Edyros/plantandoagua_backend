<?php

namespace App\Http\Controllers;

use App\Models\Planting;
use App\Models\Shop;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class LandingController extends Controller
{
    public function __invoke(): View
    {
        return view('landing', [
            'playStoreUrl' => (string) config('store.play'),
            'appStoreUrl' => (string) config('store.apple'),
            'stats' => $this->communityStats(),
        ]);
    }

    /**
     * @return array{trees: int, adopted: int, plantings: int, shops: int, cities: int, species: int}
     */
    private function communityStats(): array
    {
        $empty = [
            'trees' => 0,
            'adopted' => 0,
            'plantings' => 0,
            'shops' => 0,
            'cities' => 0,
            'species' => 0,
        ];

        try {
            if (! Schema::hasTable('plantings')) {
                return $empty;
            }

            $hasKind = Schema::hasColumn('plantings', 'kind');
            $treeCounts = $hasKind
                ? Planting::query()
                    ->selectRaw("COALESCE(SUM(CASE WHEN kind = 'adopted' THEN quantity ELSE 0 END), 0) as adopted")
                    ->selectRaw("COALESCE(SUM(CASE WHEN kind = 'adopted' THEN 0 ELSE quantity END), 0) as planted")
                    ->first()
                : null;

            return [
                'trees' => $hasKind
                    ? (int) ($treeCounts->planted ?? 0)
                    : (int) Planting::query()->sum('quantity'),
                'adopted' => $hasKind
                    ? (int) ($treeCounts->adopted ?? 0)
                    : 0,
                'plantings' => Planting::query()->count(),
                'shops' => Schema::hasTable('shops')
                    ? Shop::query()->where('visible', true)->count()
                    : 0,
                'cities' => (int) Planting::query()->whereNotNull('city')->where('city', '!=', '')->distinct()->count('city'),
                'species' => (int) Planting::query()->whereNotNull('species')->where('species', '!=', '')->distinct()->count('species'),
            ];
        } catch (Throwable) {
            return $empty;
        }
    }
}

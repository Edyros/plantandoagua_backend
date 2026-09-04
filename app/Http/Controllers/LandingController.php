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
     * @return array{trees: int, plantings: int, shops: int, cities: int, species: int}
     */
    private function communityStats(): array
    {
        $empty = [
            'trees' => 0,
            'plantings' => 0,
            'shops' => 0,
            'cities' => 0,
            'species' => 0,
        ];

        try {
            if (! Schema::hasTable('plantings')) {
                return $empty;
            }

            return [
                'trees' => (int) Planting::query()->sum('quantity'),
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

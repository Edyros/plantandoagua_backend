<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class LandingController extends Controller
{
    public function __invoke(): View
    {
        return view('index_novo', $this->storeLinks());
    }

    /**
     * @return array{playStoreUrl: string, appStoreUrl: string}
     */
    private function storeLinks(): array
    {
        return [
            'playStoreUrl' => (string) config('store.play'),
            'appStoreUrl' => (string) config('store.apple'),
        ];
    }
}

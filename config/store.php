<?php

return [

    /*
    |--------------------------------------------------------------------------
    | App store listings
    |--------------------------------------------------------------------------
    |
    | Public download links used by the marketing landing page. Update these
    | when the listings go live or the store IDs change.
    |
    */

    'play' => env(
        'PLAY_STORE_URL',
        'https://play.google.com/store/apps/details?id=com.globaltreememory.app',
    ),

    'apple' => env(
        'APP_STORE_URL',
        'https://apps.apple.com/br/search?term=Plantando%20Agua',
    ),

];

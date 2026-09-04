<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\Dev\SimulateHebronPayWebhookController;
use App\Http\Controllers\Api\HebronPayWebhookController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PlantingController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/webhooks/hebronpay', HebronPayWebhookController::class);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::put('/user', [AuthController::class, 'update']);
    Route::post('/user', [AuthController::class, 'update']);
    Route::get('/user/preferences', [AuthController::class, 'preferences']);
    Route::put('/user/preferences', [AuthController::class, 'updatePreferences']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/users/{id}', [UserController::class, 'show']);

    Route::get('/shops', [ShopController::class, 'index']);
    Route::get('/shops/me', [ShopController::class, 'me']);
    Route::post('/shops', [ShopController::class, 'store']);
    Route::get('/shops/{id}', [ShopController::class, 'show']);
    Route::post('/shops/{id}', [ShopController::class, 'update']);
    Route::put('/shops/{id}', [ShopController::class, 'update']);
    Route::patch('/shops/{id}', [ShopController::class, 'update']);
    Route::delete('/shops/{id}', [ShopController::class, 'destroy']);

    Route::get('/campaigns', [CampaignController::class, 'index']);
    Route::get('/campaigns/mine', [CampaignController::class, 'mine']);
    Route::get('/campaigns/unlocked', [CampaignController::class, 'unlocked']);
    Route::post('/campaigns', [CampaignController::class, 'store']);
    Route::post('/campaigns/redeem', [CampaignController::class, 'redeem']);
    Route::get('/campaigns/{id}', [CampaignController::class, 'show']);
    Route::post('/campaigns/{id}/status', [CampaignController::class, 'updateStatus']);
    Route::get('/campaigns/{id}/plantings', [CampaignController::class, 'plantings']);

    Route::get('/plantings/community', [PlantingController::class, 'community']);
    Route::get('/plantings', [PlantingController::class, 'index']);
    Route::post('/plantings', [PlantingController::class, 'store']);
    Route::get('/plantings/{id}', [PlantingController::class, 'show']);
    Route::post('/plantings/{id}', [PlantingController::class, 'update']);
    Route::put('/plantings/{id}', [PlantingController::class, 'update']);
    Route::patch('/plantings/{id}', [PlantingController::class, 'update']);
    Route::delete('/plantings/{id}', [PlantingController::class, 'destroy']);

    Route::get('/payments', [PaymentController::class, 'index']);
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::get('/payments/{id}', [PaymentController::class, 'show']);
    Route::post('/payments/{id}/cancel', [PaymentController::class, 'cancel']);

    if (app()->environment(['local', 'testing'])) {
        Route::get('/dev/webhooks/hebronpay', [SimulateHebronPayWebhookController::class, 'index']);
        Route::post('/dev/payments/{id}/simulate-webhook', [SimulateHebronPayWebhookController::class, 'store']);
    }
});

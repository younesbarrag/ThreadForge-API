<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CampaignBlueprintController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RawContentController;


Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'ThreadForge API is running',
    ]);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('blueprints', CampaignBlueprintController::class);

    Route::get('/raw-contents', [RawContentController::class, 'index']);
Route::post('/raw-contents', [RawContentController::class, 'store']);
Route::get('/raw-contents/{rawContent}', [RawContentController::class, 'show']);
});
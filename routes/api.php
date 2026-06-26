<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CampaignBlueprintController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RawContentController;
use App\Http\Controllers\Api\GeneratedPostController;
use App\Http\Controllers\Api\PostVersionController;



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
      Route::patch('/generated-posts/{generatedPost}/status', [GeneratedPostController::class, 'updateStatus']);
      Route::get('/generated-posts/{generatedPost}/versions', [PostVersionController::class, 'index']);
    Route::apiResource('generated-posts', GeneratedPostController::class)
        ->only(['index', 'show']);
});
<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CampaignBlueprintController;
use App\Http\Controllers\Api\GeneratedPostController;
use App\Http\Controllers\Api\GhostwriterController;
use App\Http\Controllers\Api\PostVersionController;
use App\Http\Controllers\Api\RawContentController;
use Illuminate\Support\Facades\Route;

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
    Route::post('/generated-posts/{generatedPost}/regenerate', [GeneratedPostController::class, 'regenerate']);
    Route::get('/generated-posts/{generatedPost}/versions', [PostVersionController::class, 'index']);
    Route::apiResource('generated-posts', GeneratedPostController::class)
        ->only(['index', 'show']);

    Route::get('/conversations', [GhostwriterController::class, 'indexConversations']);
    Route::post('/conversations', [GhostwriterController::class, 'storeConversation']);
    Route::get('/conversations/{conversation}', [GhostwriterController::class, 'showConversation']);
    Route::delete('/conversations/{conversation}', [GhostwriterController::class, 'destroyConversation']);
    Route::get('/conversations/{conversation}/messages', [GhostwriterController::class, 'indexMessages']);
    Route::post('/conversations/{conversation}/messages', [GhostwriterController::class, 'storeMessage']);

    Route::post('/content/repurpose', [RawContentController::class, 'store']);
});

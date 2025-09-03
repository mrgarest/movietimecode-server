<?php

use App\Http\Controllers\Api\MovieController;
use App\Http\Controllers\Api\TimecodeController;
use App\Http\Controllers\AuthController;
use App\Http\Middleware\AuthApiMiddleware;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// Route::prefix('movie')->controller(MovieController::class)->group(function () {
//     Route::get('/search', 'search');
//     Route::post('/add', 'addMovie');
//     // Route::prefix('{movieId}')->group(function () {
//     Route::prefix('{movieId}')->group(function () {
//         Route::get('/', 'viewMovie');
//         Route::prefix('timecode')->group(function () {
//             Route::middleware('auth:api')->group(function () {
//                 Route::prefix('editor')->group(function () {
//                     Route::get('/', 'editorTimecode');
//                     Route::post('/new', 'newTimecode');
//                 });
//                 Route::prefix('{timecodeId}')->group(function () {
//                     Route::delete('/', 'deleteTimecode');
//                     Route::post('/edit', 'editTimecode');
//                 });
//             });
//             Route::get('/{timecodeId}/select', 'viewMovieSelectTimecode');
//         });
//     });
// });


Route::prefix('v1')->group(function () {
    Route::get('/auth', [AuthController::class, 'extension']);
    Route::prefix('movie')->controller(MovieController::class)->group(function () {
        Route::get('/search', 'search');
        Route::get('/check', 'check');
    });
    Route::prefix('timecode')->controller(TimecodeController::class)->group(function () {
        Route::get('/search', 'search');
        Route::middleware(AuthApiMiddleware::class)->group(function () {
            Route::post('/new', 'new');
            Route::get('/editor/{movieId}', 'editor');

            Route::prefix('{timecodeId}')->group(function () {
                Route::post('/edit', 'edit');
                Route::delete('/', 'deleteTimecode');
            });
        });
        Route::post('/{timecodeId}/analytics/used', 'usedAnalytics');
        Route::get('/{timecodeId}/segment', 'segment');
    });
});

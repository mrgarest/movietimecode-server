<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PrivacyController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/privacy', [PrivacyController::class, 'index'])->name('privacy');

Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::get('/login/twitch', 'logIn');
    Route::get('/callback/twitch', 'callback');
});
<?php

use App\Http\Controllers\Api\V1\DemoIdentityController;
use App\Http\Controllers\Api\V1\SsoLoginController;
use App\Http\Middleware\EnsureDemoEnvironment;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('api/v1')->middleware(EnsureDemoEnvironment::class)->group(function (): void {
    Route::post('/demo/session', [DemoIdentityController::class, 'store']);
    Route::delete('/demo/session', [DemoIdentityController::class, 'destroy']);
    Route::post('/auth/sso/login', [SsoLoginController::class, 'store'])->middleware('throttle:5,1');
});

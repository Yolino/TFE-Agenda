<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SsoController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// OIDC/OAuth2 Routes
Route::get('/oidc/authorize', [SsoController::class, 'handleAuthorization'])->middleware('web')->name('oidc.authorize');
Route::get('/oidc/continue', [SsoController::class, 'continueAuthorization'])->middleware('web', 'auth:web')->name('oidc.continue');
Route::post('/oidc/token', [SsoController::class, 'token'])->name('oidc.token');
Route::get('/oidc/userinfo', [SsoController::class, 'userInfo'])->middleware('auth:api')->name('oidc.userinfo');
Route::get('/oidc/jwks', [SsoController::class, 'jwkSet'])->name('oidc.jwks');
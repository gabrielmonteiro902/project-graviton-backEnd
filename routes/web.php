<?php

use App\Http\Controllers\Auth\GithubAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name'    => 'Graviton E-commerce API',
        'version' => '1.0.0',
        'status'  => 'running',
        'docs'    => '/api/v1',
    ]);
});

// GitHub OAuth — navegação do browser (full-page redirect), por isso fica no grupo web
// (precisa da sessão para guardar o `state` anti-CSRF entre redirect e callback).
Route::middleware('throttle:10,1')->group(function () {
    Route::get('/auth/github/redirect', [GithubAuthController::class, 'redirect']);
    Route::get('/auth/github/callback', [GithubAuthController::class, 'callback']);
});

<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\ContributionController;
use App\Http\Controllers\ContributorController;
use App\Http\Controllers\OrbitConnectionController;
use App\Http\Controllers\RepositoryController;
use App\Http\Controllers\TenantController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Graviton E-commerce Multi-tenant
|--------------------------------------------------------------------------
|
| Todas as rotas ficam com prefixo /api automaticamente pelo Laravel.
| Multi-tenancy:
|   - Rotas públicas: identificação via header X-Tenant-ID
|   - Rotas protegidas: tenant_id extraído do JWT pelo Authenticate middleware
|
*/

Route::prefix('v1')->group(function () {

    // -------------------------------------------------------------------------
    // Gestão de Tenants (stores/lojas)
    // -------------------------------------------------------------------------
    Route::apiResource('tenants', TenantController::class);

    // -------------------------------------------------------------------------
    // Autenticação pública
    // -------------------------------------------------------------------------
    Route::post('register', [AdminAuthController::class, 'register']);

    Route::middleware('tenant')->group(function () {
        Route::post('login', [AdminAuthController::class, 'login']);
    });

    // -------------------------------------------------------------------------
    // Rotas protegidas por JWT
    // -------------------------------------------------------------------------
    Route::middleware('auth.jwt')->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout']);
        Route::post('refresh', [AdminAuthController::class, 'refresh']);
        Route::get('me', [AdminAuthController::class, 'me']);

        // CRUD de admins (escopo automático por tenant via tenant_id no JWT)
        Route::apiResource('admins', AdminController::class);

        // GitHub Org-Sync
        Route::apiResource('repositories', RepositoryController::class);
        Route::delete('repositories', [RepositoryController::class, 'destroyBulk']);
        Route::apiResource('contributors', ContributorController::class);

        // Contributions — usa query param ?repository_id= no index
        Route::get('contributions', [ContributionController::class, 'index']);
        Route::post('contributions', [ContributionController::class, 'store']);
        Route::delete('contributions/{id}', [ContributionController::class, 'destroy']);

        // Dois Corpos — conexões orbitais salvas
        Route::get('orbit-connections', [OrbitConnectionController::class, 'index']);
        Route::post('orbit-connections', [OrbitConnectionController::class, 'store']);
        Route::patch('orbit-connections/{id}', [OrbitConnectionController::class, 'update']);
        Route::delete('orbit-connections/{id}', [OrbitConnectionController::class, 'destroy']);
    });
});

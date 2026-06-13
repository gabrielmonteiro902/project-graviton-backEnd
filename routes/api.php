<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\AdminAuthController;
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
    // Autenticação (público — requer header X-Tenant-ID)
    // -------------------------------------------------------------------------
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
    });
});

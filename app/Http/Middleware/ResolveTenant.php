<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;

/**
 * Resolve o tenant a partir do header X-Tenant-ID.
 * Usado nas rotas públicas (ex: login) onde ainda não há JWT.
 * Nas rotas protegidas, o tenant é resolvido pelo Authenticate middleware via JWT.
 */
class ResolveTenant
{
    public function handle(Request $request, Closure $next): mixed
    {
        $tenantId = $request->header('X-Tenant-ID');

        if ($tenantId && ! app()->has('current_tenant')) {
            $tenant = Tenant::find($tenantId);
            if ($tenant) {
                app()->instance('current_tenant', $tenant);
            }
        }

        return $next($request);
    }
}

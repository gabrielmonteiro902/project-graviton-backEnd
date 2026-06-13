<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

class Authenticate
{
    public function handle(Request $request, Closure $next): mixed
    {
        try {
            $payload = JWTAuth::parseToken()->getPayload();

            // Inicializa o tenant no container antes de autenticar,
            // pois o model Admin precisa do tenant_id para validação.
            $tenantId = $payload->get('tenant_id');
            if ($tenantId) {
                $tenant = Tenant::find($tenantId);
                if ($tenant) {
                    app()->instance('current_tenant', $tenant);
                }
            }

            $user = JWTAuth::parseToken()->authenticate();

            if (! $user) {
                return response()->json(['message' => 'Não autorizado'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['message' => 'Token inválido ou expirado'], 401);
        }

        return $next($request);
    }
}

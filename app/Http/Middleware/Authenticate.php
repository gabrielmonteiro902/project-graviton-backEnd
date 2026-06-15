<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

class Authenticate
{
    /** Mesmo nome usado pelo AdminAuthController ao emitir o cookie. */
    private const TOKEN_COOKIE = 'graviton_token';

    public function handle(Request $request, Closure $next): mixed
    {
        // Token vem do cookie HttpOnly (padrão atual) OU do header Authorization
        // (compatibilidade — ferramentas/testes que ainda usam Bearer continuam funcionando).
        $token = $request->bearerToken() ?: $request->cookie(self::TOKEN_COOKIE);

        if (! $token) {
            return response()->json(['message' => 'Token ausente'], 401);
        }

        try {
            JWTAuth::setToken($token);

            // Inicializa o tenant no container antes de autenticar,
            // pois o model Admin precisa do tenant_id para validação.
            $tenantId = JWTAuth::getPayload()->get('tenant_id');
            if ($tenantId) {
                $tenant = Tenant::find($tenantId);
                if ($tenant) {
                    app()->instance('current_tenant', $tenant);
                }
            }

            $user = JWTAuth::authenticate();

            if (! $user) {
                return response()->json(['message' => 'Não autorizado'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['message' => 'Token inválido ou expirado'], 401);
        }

        return $next($request);
    }
}

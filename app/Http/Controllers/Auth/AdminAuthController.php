<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AdminAuthController extends Controller
{
    /** Nome do cookie HttpOnly que carrega o JWT. */
    private const TOKEN_COOKIE = 'graviton_token';

    public function __construct(private readonly AuthService $authService) {}

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id'      => 'required|string|alpha_dash|max:50|unique:tenants,id',
            'tenant_name'    => 'required|string|max:255',
            'tenant_email'   => 'required|email|unique:tenants,email',
            'name_admin'     => 'required|string|max:255',
            'email_admin'    => 'required|email',
            'password_admin' => 'required|string|min:8',
        ]);

        $result = $this->authService->register($validated);

        $response = response()->json([
            'message'    => 'Conta criada com sucesso.',
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'tenant_id'  => $result['tenant']->id,
            'admin'      => [
                'id'          => $result['admin']->id,
                'name_admin'  => $result['admin']->name_admin,
                'email_admin' => $result['admin']->email_admin,
            ],
        ], 201);

        return $this->withTokenCookie($response, $result['token']);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email_admin'    => 'required|email',
            'password_admin' => 'required|string',
        ]);

        try {
            $result = $this->authService->login(
                $request->email_admin,
                $request->password_admin,
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }

        $response = response()->json([
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'tenant_id'  => $result['tenant']->id,
        ]);

        return $this->withTokenCookie($response, $result['token']);
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout();

        $response = response()->json(['message' => 'Logout realizado com sucesso']);

        return $this->forgetTokenCookie($response);
    }

    public function me(): JsonResponse
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return response()->json([
            'id'         => $admin->id,
            'name_admin' => $admin->name_admin,
            'email_admin'=> $admin->email_admin,
            'tenant_id'  => $admin->tenant_id,
            'created_at' => $admin->created_at,
        ]);
    }

    public function refresh(): JsonResponse
    {
        $token = $this->authService->refresh();

        $response = response()->json([
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ]);

        return $this->withTokenCookie($response, $token);
    }

    /**
     * Anexa o JWT num cookie HttpOnly — inacessível a JavaScript, o que mitiga
     * roubo de token via XSS. O corpo da resposta NÃO contém o token de propósito:
     * o frontend nunca precisa (nem deve) manipulá-lo.
     */
    private function withTokenCookie(JsonResponse $response, string $token): JsonResponse
    {
        return $response->withCookie(cookie(
            name: self::TOKEN_COOKIE,
            value: $token,
            minutes: (int) config('jwt.ttl'),
            path: '/',
            domain: null,
            secure: app()->environment('production'),
            httpOnly: true,
            raw: false,
            // 'lax' cobre o dev (mesmo site, portas diferentes). Em produção com
            // front e back em domínios distintos, troque para 'none' (exige HTTPS).
            sameSite: 'lax',
        ));
    }

    private function forgetTokenCookie(JsonResponse $response): JsonResponse
    {
        return $response->withCookie(cookie()->forget(self::TOKEN_COOKIE, '/'));
    }
}

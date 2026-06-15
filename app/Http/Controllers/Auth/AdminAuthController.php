<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Concerns\IssuesTokenCookie;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AdminAuthController extends Controller
{
    use IssuesTokenCookie;

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

        return $response->withCookie($this->tokenCookie($result['token']));
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

        return $response->withCookie($this->tokenCookie($result['token']));
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout();

        $response = response()->json(['message' => 'Logout realizado com sucesso']);

        return $response->withCookie($this->forgetTokenCookie());
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
            'github_id'  => $admin->github_id,
            'avatar_url' => $admin->avatar_url,
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

        return $response->withCookie($this->tokenCookie($token));
    }
}

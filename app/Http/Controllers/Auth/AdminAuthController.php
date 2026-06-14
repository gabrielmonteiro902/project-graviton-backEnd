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
    public function __construct(private readonly AuthService $authService) {}

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'tenant_id'      => 'required|string|alpha_dash|max:50|unique:tenants,id',
            'tenant_name'    => 'required|string|max:255',
            'tenant_email'   => 'required|email|unique:tenants,email',
            'plan'           => 'nullable|string|in:free,starter,pro,enterprise',
            'name_admin'     => 'required|string|max:255',
            'email_admin'    => 'required|email',
            'password_admin' => 'required|string|min:8',
        ]);

        $result = $this->authService->register($request->validated());

        return response()->json([
            'message'      => 'Conta criada com sucesso.',
            'access_token' => $result['token'],
            'token_type'   => 'bearer',
            'expires_in'   => config('jwt.ttl') * 60,
            'tenant_id'    => $result['tenant']->id,
            'admin'        => [
                'id'          => $result['admin']->id,
                'name_admin'  => $result['admin']->name_admin,
                'email_admin' => $result['admin']->email_admin,
            ],
        ], 201);
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

        return response()->json([
            'access_token' => $result['token'],
            'token_type'   => 'bearer',
            'expires_in'   => config('jwt.ttl') * 60,
            'tenant_id'    => $result['tenant']->id,
        ]);
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return response()->json(['message' => 'Logout realizado com sucesso']);
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

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => config('jwt.ttl') * 60,
        ]);
    }
}

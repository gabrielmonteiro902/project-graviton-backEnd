<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
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

        $result = DB::transaction(function () use ($request) {
            $tenant = Tenant::create([
                'id'    => $request->tenant_id,
                'name'  => $request->tenant_name,
                'email' => $request->tenant_email,
                'plan'  => $request->plan ?? 'free',
            ]);

            $admin = Admin::create([
                'name_admin'     => $request->name_admin,
                'email_admin'    => $request->email_admin,
                'password_admin' => Hash::make($request->password_admin),
                'tenant_id'      => $tenant->id,
            ]);

            return ['tenant' => $tenant, 'admin' => $admin];
        });

        $token = auth('admin')->login($result['admin']);

        return response()->json([
            'message'      => 'Conta criada com sucesso.',
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => config('jwt.ttl') * 60,
            'tenant_id'    => $result['tenant']->id,
            'admin'        => [
                'id'         => $result['admin']->id,
                'name_admin' => $result['admin']->name_admin,
                'email_admin'=> $result['admin']->email_admin,
            ],
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email_admin'    => 'required|email',
            'password_admin' => 'required|string',
            'tenant_id'      => 'required|string',
        ]);

        $tenant = Tenant::find($request->tenant_id);

        if (! $tenant) {
            return response()->json(['message' => 'Tenant não encontrado'], 404);
        }

        $admin = Admin::where('email_admin', $request->email_admin)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (! $admin || ! Hash::check($request->password_admin, $admin->password_admin)) {
            return response()->json(['message' => 'Credenciais inválidas'], 401);
        }

        $token = auth('admin')->login($admin);

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => config('jwt.ttl') * 60,
            'tenant_id'    => $tenant->id,
        ]);
    }

    public function logout(): JsonResponse
    {
        auth('admin')->logout();

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
        $token = auth('admin')->refresh();

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => config('jwt.ttl') * 60,
        ]);
    }
}

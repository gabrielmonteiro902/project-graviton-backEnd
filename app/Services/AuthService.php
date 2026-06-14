<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AuthService
{
    public function register(array $data): array
    {
        $result = DB::transaction(function () use ($data) {
            $tenant = Tenant::create([
                'id'    => $data['tenant_id'],
                'name'  => $data['tenant_name'],
                'email' => $data['tenant_email'],
                'plan'  => $data['plan'] ?? 'free',
            ]);

            $admin = Admin::create([
                'name_admin'     => $data['name_admin'],
                'email_admin'    => $data['email_admin'],
                'password_admin' => Hash::make($data['password_admin']),
                'tenant_id'      => $tenant->id,
            ]);

            return ['tenant' => $tenant, 'admin' => $admin];
        });

        $token = auth('admin')->login($result['admin']);

        return [
            'token'  => $token,
            'tenant' => $result['tenant'],
            'admin'  => $result['admin'],
        ];
    }

    public function login(string $email, string $password, string $tenantId): array
    {
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            throw new RuntimeException('Tenant não encontrado', 404);
        }

        $admin = Admin::where('email_admin', $email)
            ->where('tenant_id', $tenantId)
            ->first();

        if (! $admin || ! Hash::check($password, $admin->password_admin)) {
            throw new RuntimeException('Credenciais inválidas', 401);
        }

        return [
            'token'  => auth('admin')->login($admin),
            'tenant' => $tenant,
        ];
    }

    public function logout(): void
    {
        auth('admin')->logout();
    }

    public function refresh(): string
    {
        return auth('admin')->refresh();
    }
}

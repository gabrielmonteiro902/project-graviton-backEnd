<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class AuthService
{
    public function register(array $data): array
    {
        $result = DB::transaction(function () use ($data) {
            // Novos tenants sempre iniciam em 'free'; upgrade só via billing.
            // forceFill porque 'plan' foi removido de $fillable (anti mass-assignment).
            $tenant = new Tenant();
            $tenant->forceFill([
                'id'    => $data['tenant_id'],
                'name'  => $data['tenant_name'],
                'email' => $data['tenant_email'],
                'plan'  => 'free',
            ])->save();

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

    public function login(string $email, string $password): array
    {
        $admin = Admin::where('email_admin', $email)->first();

        // Contas OAuth têm password_admin null — não podem logar por senha.
        if (! $admin || ! $admin->password_admin || ! Hash::check($password, $admin->password_admin)) {
            throw new RuntimeException('Credenciais inválidas', 401);
        }

        return [
            'token'  => auth('admin')->login($admin),
            'tenant' => $admin->tenant,
        ];
    }

    /**
     * Login via GitHub: vincula a um admin existente pelo e-mail; se não houver,
     * cria um tenant + admin novos (conta OAuth, sem senha). O token OAuth é
     * persistido criptografado (cast no model).
     */
    public function findOrCreateFromGithub(array $ghUser, string $email, string $githubToken): Admin
    {
        $admin = Admin::where('email_admin', $email)->first();

        if ($admin) {
            $admin->forceFill([
                'github_id'    => $ghUser['id'] ?? $admin->github_id,
                'avatar_url'   => $ghUser['avatar_url'] ?? $admin->avatar_url,
                'github_token' => $githubToken,
            ])->save();

            return $admin;
        }

        return DB::transaction(function () use ($ghUser, $email, $githubToken) {
            $login = $ghUser['login'] ?? 'github-user';
            $name  = ! empty($ghUser['name']) ? $ghUser['name'] : $login;

            $tenant = new Tenant();
            $tenant->forceFill([
                'id'    => $this->uniqueTenantId($login),
                'name'  => $name,
                'email' => $email,
                'plan'  => 'free',
            ])->save();

            $admin = new Admin();
            $admin->forceFill([
                'tenant_id'      => $tenant->id,
                'name_admin'     => $name,
                'email_admin'    => $email,
                'password_admin' => null,
                'github_id'      => $ghUser['id'] ?? null,
                'avatar_url'     => $ghUser['avatar_url'] ?? null,
                'github_token'   => $githubToken,
            ])->save();

            return $admin;
        });
    }

    public function logout(): void
    {
        auth('admin')->logout();
    }

    public function refresh(): string
    {
        return auth('admin')->refresh();
    }

    private function uniqueTenantId(string $login): string
    {
        $base = Str::slug($login) ?: 'github';
        $base = substr($base, 0, 40);

        $id = $base;
        while (Tenant::whereKey($id)->exists()) {
            $id = $base . '-' . Str::lower(Str::random(5));
        }

        return $id;
    }
}

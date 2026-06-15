<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Tenant;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;

class AdminAuthLoginTest extends TestCase
{
    private function createTenantAndAdmin(): array
    {
        $tenant = Tenant::create([
            'id' => 'test-tenant',
            'name' => 'Test Tenant',
            'email' => 'tenant@test.com',
            'plan' => 'starter',
        ]);

        $admin = Admin::create([
            'tenant_id' => $tenant->id,
            'name_admin' => 'Test Admin',
            'email_admin' => 'admin@test.com',
            'password_admin' => Hash::make('password123'),
        ]);

        return [$tenant, $admin];
    }

    public function test_can_login_with_valid_credentials(): void
    {
        [$tenant] = $this->createTenantAndAdmin();

        $response = $this->withHeaders(['X-Tenant-ID' => $tenant->id])
            ->postJson('/api/v1/login', [
                'email_admin' => 'admin@test.com',
                'password_admin' => 'password123',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token_type', 'expires_in', 'tenant_id'])
            ->assertCookie('graviton_token');

        // O JWT vai no cookie HttpOnly, NUNCA no corpo da resposta.
        $this->assertArrayNotHasKey('access_token', $response->json());
    }

    public function test_login_fails_with_wrong_password(): void
    {
        [$tenant] = $this->createTenantAndAdmin();

        $response = $this->withHeaders(['X-Tenant-ID' => $tenant->id])
            ->postJson('/api/v1/login', [
                'email_admin' => 'admin@test.com',
                'password_admin' => 'wrong-password',
            ]);

        $response->assertStatus(401);
    }

    public function test_protected_route_requires_token(): void
    {
        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(401);
    }
}

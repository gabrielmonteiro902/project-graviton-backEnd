<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Tenant;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class RepositoryTest extends TestCase
{
    private function actingAsAdmin(): array
    {
        // Não dispara o job de sync real (que faria chamada HTTP ao GitHub) nos testes.
        Queue::fake();

        $tenant = Tenant::create([
            'id' => 'repo-tenant',
            'name' => 'Repo Tenant',
            'email' => 'repo@test.com',
            'plan' => 'starter',
        ]);

        $admin = Admin::create([
            'tenant_id' => $tenant->id,
            'name_admin' => 'Repo Admin',
            'email_admin' => 'repoadmin@test.com',
            'password_admin' => Hash::make('password123'),
        ]);

        $token = JWTAuth::fromUser($admin);

        return [$admin, $token];
    }

    public function test_can_create_repository(): void
    {
        [, $token] = $this->actingAsAdmin();

        $response = $this->withToken($token)
            ->postJson('/api/v1/repositories', [
                'github_url' => 'https://github.com/laravel/laravel',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['repository' => ['id', 'github_owner', 'github_repo', 'status']]);
    }

    public function test_can_list_repositories(): void
    {
        [, $token] = $this->actingAsAdmin();

        $response = $this->withToken($token)
            ->getJson('/api/v1/repositories');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'current_page', 'total']);
    }

    public function test_cannot_create_duplicate_repository(): void
    {
        [, $token] = $this->actingAsAdmin();

        $payload = ['github_url' => 'https://github.com/laravel/laravel'];

        $this->withToken($token)->postJson('/api/v1/repositories', $payload);
        $response = $this->withToken($token)->postJson('/api/v1/repositories', $payload);

        $response->assertStatus(409);
    }
}
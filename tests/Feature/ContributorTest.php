<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Tenant;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class ContributorTest extends TestCase
{
    private function actingAsAdmin(): string
    {
        $tenant = Tenant::create([
            'id' => 'contrib-tenant',
            'name' => 'Contrib Tenant',
            'email' => 'contrib@test.com',
            'plan' => 'starter',
        ]);

        $admin = Admin::create([
            'tenant_id' => $tenant->id,
            'name_admin' => 'Admin',
            'email_admin' => 'admin@contrib.com',
            'password_admin' => Hash::make('password123'),
        ]);

        return JWTAuth::fromUser($admin);
    }

    public function test_can_create_contributor(): void
    {
        $token = $this->actingAsAdmin();

        $response = $this->withToken($token)->postJson('/api/v1/contributors', [
            'github_id' => 12345,
            'username' => 'torvalds',
            'hireable' => true,
            'location' => 'Portland, OR',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['username' => 'torvalds']);
    }

    public function test_can_filter_contributors_by_hireable(): void
    {
        $token = $this->actingAsAdmin();

        $this->withToken($token)->postJson('/api/v1/contributors', [
            'github_id' => 111,
            'username' => 'hireable-dev',
            'hireable' => true,
        ]);

        $this->withToken($token)->postJson('/api/v1/contributors', [
            'github_id' => 222,
            'username' => 'not-hireable-dev',
            'hireable' => false,
        ]);

        $response = $this->withToken($token)->getJson('/api/v1/contributors?hireable=true');

        $response->assertStatus(200);
        // /contributors pagina os resultados; os itens ficam em data
        $data = $response->json();
        $this->assertCount(1, $data['data']);
        $this->assertEquals('hireable-dev', $data['data'][0]['username']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Tenant;
use App\Models\Repository;
use App\Models\Contributor;
use App\Models\Contribution;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class ContributionGravityTest extends TestCase
{
    public function test_gravity_is_calculated_correctly(): void
    {
        $tenant = Tenant::create([
            'id' => 'gravity-tenant',
            'name' => 'Gravity Tenant',
            'email' => 'gravity@test.com',
            'plan' => 'pro',
        ]);

        $admin = Admin::create([
            'tenant_id' => $tenant->id,
            'name_admin' => 'Admin',
            'email_admin' => 'admin@gravity.com',
            'password_admin' => Hash::make('password123'),
        ]);

        $token = JWTAuth::fromUser($admin);

        $repo = Repository::create([
            'tenant_id' => $tenant->id,
            'github_owner' => 'laravel',
            'github_repo' => 'framework',
            'status' => 'active',
        ]);

        $c1 = Contributor::create([
            'tenant_id' => $tenant->id,
            'github_id' => 1,
            'username' => 'dev-a',
        ]);

        $c2 = Contributor::create([
            'tenant_id' => $tenant->id,
            'github_id' => 2,
            'username' => 'dev-b',
        ]);

        Contribution::create([
            'tenant_id' => $tenant->id,
            'repository_id' => $repo->id,
            'contributor_id' => $c1->id,
            'commits_count' => 75,
        ]);

        Contribution::create([
            'tenant_id' => $tenant->id,
            'repository_id' => $repo->id,
            'contributor_id' => $c2->id,
            'commits_count' => 25,
        ]);

        $response = $this->withToken($token)
            ->getJson("/api/v1/contributions?repository_id={$repo->id}");

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertEquals(100, $data['total_commits']);

        $gravities = collect($data['contributions'])->pluck('gravity', 'contributor.username');
        $this->assertEquals(0.75, $gravities['dev-a']);
        $this->assertEquals(0.25, $gravities['dev-b']);
    }
}

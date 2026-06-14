<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Contribution;
use App\Models\Contributor;
use App\Models\OrbitConnection;
use App\Models\Repository;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Tenant demo
        $tenant = Tenant::create([
            'id'    => 'graviton-demo',
            'name'  => 'Graviton Demo',
            'email' => 'demo@graviton.dev',
            'plan'  => 'pro',
        ]);

        // Admin principal
        Admin::create([
            'id'             => Str::uuid(),
            'tenant_id'      => $tenant->id,
            'name_admin'     => 'Demo Admin',
            'email_admin'    => 'admin@graviton.dev',
            'password_admin' => Hash::make('password'),
        ]);

        // Repositórios
        $repoA = Repository::create([
            'id'           => Str::uuid(),
            'tenant_id'    => $tenant->id,
            'github_owner' => 'laravel',
            'github_repo'  => 'laravel',
            'status'       => 'active',
            'last_synced_at' => now(),
        ]);

        $repoB = Repository::create([
            'id'           => Str::uuid(),
            'tenant_id'    => $tenant->id,
            'github_owner' => 'laravel',
            'github_repo'  => 'framework',
            'status'       => 'active',
            'last_synced_at' => now(),
        ]);

        // Contributors
        $contributors = [
            ['github_id' => 463230,  'username' => 'taylorotwell',  'hireable' => false, 'location' => 'Memphis, TN'],
            ['github_id' => 878907,  'username' => 'driesvints',    'hireable' => true,  'location' => 'Ghent, Belgium'],
            ['github_id' => 1533232, 'username' => 'nunomaduro',    'hireable' => true,  'location' => 'Portugal'],
        ];

        $createdContributors = [];
        foreach ($contributors as $data) {
            $createdContributors[] = Contributor::create([
                'id'         => Str::uuid(),
                'tenant_id'  => $tenant->id,
                'github_id'  => $data['github_id'],
                'username'   => $data['username'],
                'hireable'   => $data['hireable'],
                'location'   => $data['location'],
                'avatar_url' => "https://avatars.githubusercontent.com/u/{$data['github_id']}",
            ]);
        }

        // Contributions para repoA (laravel/laravel)
        $commitsA = [320, 85, 42];
        foreach ($createdContributors as $i => $contributor) {
            Contribution::create([
                'id'             => Str::uuid(),
                'tenant_id'      => $tenant->id,
                'repository_id'  => $repoA->id,
                'contributor_id' => $contributor->id,
                'commits_count'  => $commitsA[$i],
                'additions'      => $commitsA[$i] * 12,
                'deletions'      => $commitsA[$i] * 3,
            ]);
        }

        // Contributions para repoB (laravel/framework)
        $commitsB = [1240, 310, 195];
        foreach ($createdContributors as $i => $contributor) {
            Contribution::create([
                'id'             => Str::uuid(),
                'tenant_id'      => $tenant->id,
                'repository_id'  => $repoB->id,
                'contributor_id' => $contributor->id,
                'commits_count'  => $commitsB[$i],
                'additions'      => $commitsB[$i] * 18,
                'deletions'      => $commitsB[$i] * 5,
            ]);
        }

        // Orbit Connection entre os dois repos
        OrbitConnection::create([
            'id'                     => Str::uuid(),
            'tenant_id'              => $tenant->id,
            'name'                   => 'Laravel Core ↔ App',
            'primary_repository_id'  => $repoB->id,
            'secondary_repository_id' => $repoA->id,
        ]);
    }
}

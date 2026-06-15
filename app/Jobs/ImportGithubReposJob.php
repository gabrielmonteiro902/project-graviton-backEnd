<?php

namespace App\Jobs;

use App\Models\Admin;
use App\Models\Repository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Lista os repositórios PÚBLICOS do usuário recém-logado via GitHub e cria
 * um Repository por repo, disparando o sync com o token do próprio usuário.
 */
class ImportGithubReposJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public string $adminId, public string $tenantId) {}

    public function handle(): void
    {
        $admin = Admin::find($this->adminId);
        if (! $admin || ! $admin->github_token) {
            return;
        }

        $token = $admin->github_token; // descriptografado pelo cast 'encrypted'

        $response = Http::withToken($token)
            ->withHeaders(['Accept' => 'application/vnd.github+json'])
            ->get('https://api.github.com/user/repos', [
                'per_page'    => 100,
                'visibility'  => 'public',
                'affiliation' => 'owner',
                'sort'        => 'updated',
            ]);

        if ($response->failed()) {
            Log::warning('Falha ao listar repositórios do GitHub no import', [
                'admin'  => $this->adminId,
                'status' => $response->status(),
            ]);
            return;
        }

        foreach ($response->json() as $repo) {
            // Defesa: nunca importa privado, mesmo se a API trouxer algum.
            if (($repo['private'] ?? false) === true) {
                continue;
            }

            $fullName = $repo['full_name'] ?? '';
            if (! str_contains($fullName, '/')) {
                continue;
            }

            [$owner, $name] = explode('/', $fullName, 2);

            $repository = Repository::firstOrCreate(
                [
                    'tenant_id'    => $this->tenantId,
                    'github_owner' => $owner,
                    'github_repo'  => $name,
                ],
                ['status' => 'syncing'],
            );

            // Sincroniza com o token do próprio usuário (rate-limit e acesso dele).
            SyncGitHubRepositoryJob::dispatch($repository, $token);
        }
    }
}

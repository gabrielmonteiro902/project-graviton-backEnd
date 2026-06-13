<?php

namespace App\Jobs;

use App\Models\Contribution;
use App\Models\Contributor;
use App\Models\Repository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncGitHubRepositoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    protected string $tenantId;

    public function __construct(protected Repository $repository)
    {
        $this->tenantId = $repository->tenant_id;
    }

    public function handle(): void
    {
        $this->repository->update(['status' => 'syncing']);

        $token   = config('services.github.token');
        $baseUrl = config('services.github.base_url');
        $owner   = $this->repository->github_owner;
        $repo    = $this->repository->github_repo;

        // Busca top 15 por commits — 1 única chamada à API
        $top15 = $this->fetchTop15($baseUrl, $owner, $repo, $token);

        if ($top15->isEmpty()) {
            $this->repository->update(['status' => 'error', 'last_synced_at' => now()]);
            return;
        }

        // Busca perfis em paralelo (1 batch de chamadas)
        $profiles = $this->fetchProfiles($top15->pluck('login'), $baseUrl, $token);

        DB::transaction(function () use ($top15, $profiles) {
            foreach ($top15 as $item) {
                $username = $item['login'];
                $profile  = $profiles[$username] ?? [];

                $contributor = Contributor::updateOrCreate(
                    [
                        'tenant_id' => $this->tenantId,
                        'github_id' => $item['id'],
                    ],
                    [
                        'username'   => $username,
                        'avatar_url' => $item['avatar_url'] ?? null,
                        'hireable'   => $profile['hireable'] ?? false,
                        'location'   => $profile['location'] ?? null,
                        'company'    => $profile['company'] ?? null,
                    ]
                );

                Contribution::updateOrCreate(
                    [
                        'tenant_id'      => $this->tenantId,
                        'repository_id'  => $this->repository->id,
                        'contributor_id' => $contributor->id,
                    ],
                    [
                        'commits_count' => $item['contributions'] ?? 0,
                        'additions'     => 0,
                        'deletions'     => 0,
                    ]
                );
            }
        });

        $this->repository->update([
            'status'         => 'active',
            'last_synced_at' => now(),
        ]);
    }

    public function failed(Throwable $e): void
    {
        Log::error("SyncGitHubRepositoryJob falhou: {$this->repository->github_owner}/{$this->repository->github_repo}", [
            'error' => $e->getMessage(),
        ]);

        $this->repository->update(['status' => 'error']);
    }

    // Busca a primeira página de contributors ordenada por commits (já vem ordenada pela API)
    // e filtra bots — 1 única chamada à API do GitHub
    private function fetchTop15(string $baseUrl, string $owner, string $repo, ?string $token): Collection
    {
        $response = Http::withToken($token)
            ->withHeaders(['Accept' => 'application/vnd.github+json'])
            ->get("{$baseUrl}/repos/{$owner}/{$repo}/contributors", [
                'per_page' => 15,
                'page'     => 1,
                'anon'     => false,
            ]);

        if ($response->failed()) {
            Log::warning("GitHub API falhou para {$owner}/{$repo}: " . $response->status());
            return collect();
        }

        return collect($response->json())
            ->filter(fn($c) => ($c['type'] ?? '') === 'User') // exclui bots
            ->take(15)
            ->values();
    }

    // Busca perfis em chamadas individuais mas sem transação por volta de cada uma
    private function fetchProfiles(Collection $usernames, string $baseUrl, ?string $token): array
    {
        $profiles = [];

        foreach ($usernames as $username) {
            $response = Http::withToken($token)
                ->withHeaders(['Accept' => 'application/vnd.github+json'])
                ->get("{$baseUrl}/users/{$username}");

            if ($response->ok()) {
                $profiles[$username] = $response->json();
            }
        }

        return $profiles;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Contribution;
use App\Models\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContributionController extends Controller
{
    private function tenantId(): string
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin->tenant_id;
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'repository_id' => 'required|uuid',
        ]);

        // Garante que o repositório pertence ao tenant
        $repository = Repository::where('id', $request->repository_id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();

        $totalCommits = $repository->contributions()->sum('commits_count');

        $contributions = $repository->contributions()
            ->with('contributor:id,username,avatar_url,hireable,location,company')
            ->orderByDesc('commits_count')
            ->get()
            ->map(function (Contribution $contribution) use ($totalCommits) {
                return [
                    'id'             => $contribution->id,
                    'contributor'    => $contribution->contributor,
                    'commits_count'  => $contribution->commits_count,
                    'additions'      => $contribution->additions,
                    'deletions'      => $contribution->deletions,
                    // G = C / T — força gravitacional para o Three.js
                    'gravity'        => $totalCommits > 0
                        ? round($contribution->commits_count / $totalCommits, 4)
                        : 0.0,
                    'updated_at'     => $contribution->updated_at,
                ];
            });

        return response()->json([
            'repository'    => $repository,
            'total_commits' => $totalCommits,
            'contributions' => $contributions,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'repository_id'  => 'required|uuid|exists:repositories,id',
            'contributor_id' => 'required|uuid|exists:contributors,id',
            'commits_count'  => 'required|integer|min:0',
            'additions'      => 'nullable|integer|min:0',
            'deletions'      => 'nullable|integer|min:0',
        ]);

        // Garante que repo e contributor pertencem ao mesmo tenant
        Repository::where('id', $data['repository_id'])
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();

        $contribution = Contribution::updateOrCreate(
            [
                'tenant_id'      => $this->tenantId(),
                'repository_id'  => $data['repository_id'],
                'contributor_id' => $data['contributor_id'],
            ],
            [
                'commits_count' => $data['commits_count'],
                'additions'     => $data['additions'] ?? 0,
                'deletions'     => $data['deletions'] ?? 0,
            ]
        );

        return response()->json($contribution, $contribution->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(string $id): JsonResponse
    {
        $contribution = Contribution::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();

        $contribution->delete();

        return response()->json(['message' => 'Contribuição removida com sucesso']);
    }
}

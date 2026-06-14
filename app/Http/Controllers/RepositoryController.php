<?php

namespace App\Http\Controllers;

use App\Jobs\SyncGitHubRepositoryJob;
use App\Models\Admin;
use App\Models\Repository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RepositoryController extends Controller
{
    private function tenantId(): string
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin->tenant_id;
    }

    public function index(): JsonResponse
    {
        $repositories = Repository::where('tenant_id', $this->tenantId())
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($repositories);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'github_url' => 'required|url',
        ]);

        try {
            $path = trim(parse_url($data['github_url'], PHP_URL_PATH), '/');
            $parts = explode('/', $path);

            if (count($parts) < 2 || empty($parts[0]) || empty($parts[1])) {
                return response()->json([
                    'error' => 'URL inválida. Use o formato: https://github.com/owner/repo',
                ], 422);
            }

            $repository = Repository::create([
                'tenant_id'    => $this->tenantId(),
                'github_owner' => $parts[0],
                'github_repo'  => $parts[1],
                'status'       => 'syncing',
            ]);

            SyncGitHubRepositoryJob::dispatch($repository);

            return response()->json([
                'message'    => 'Repositório registrado com sucesso. Sincronização iniciada.',
                'repository' => $repository,
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Falha ao processar solicitação: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        $repository = Repository::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();

        return response()->json($repository);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $repository = Repository::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();

        $data = $request->validate([
            'status' => 'sometimes|string|in:active,syncing,error',
            'last_synced_at' => 'sometimes|nullable|date',
        ]);

        $repository->update($data);

        return response()->json($repository);
    }

    public function destroy(string $id): JsonResponse
    {
        $repository = Repository::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();

        if ($repository->status === 'syncing') {
            return response()->json([
                'error' => 'Não é possível excluir um repositório que está sendo sincronizado.',
            ], 409);
        }

        $contributionsCount = $repository->contributions()->count();
        $repository->delete();

        return response()->json([
            'message'              => 'Repositório removido com sucesso.',
            'deleted_contributions' => $contributionsCount,
        ]);
    }

    public function destroyBulk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'required|uuid',
        ]);

        $tenantId = $this->tenantId();

        $repositories = Repository::whereIn('id', $data['ids'])
            ->where('tenant_id', $tenantId)
            ->get();

        $syncingIds = $repositories->where('status', 'syncing')->pluck('id');

        if ($syncingIds->isNotEmpty()) {
            return response()->json([
                'error'       => 'Alguns repositórios estão sendo sincronizados e não podem ser excluídos.',
                'syncing_ids' => $syncingIds->values(),
            ], 409);
        }

        $notFoundIds = collect($data['ids'])->diff($repositories->pluck('id'));
        if ($notFoundIds->isNotEmpty()) {
            return response()->json([
                'error'          => 'Um ou mais repositórios não foram encontrados.',
                'not_found_ids'  => $notFoundIds->values(),
            ], 404);
        }

        $contributionsCount = 0;
        foreach ($repositories as $repository) {
            $contributionsCount += $repository->contributions()->count();
            $repository->delete();
        }

        return response()->json([
            'message'               => 'Repositórios removidos com sucesso.',
            'deleted_repositories'  => $repositories->count(),
            'deleted_contributions' => $contributionsCount,
        ]);
    }
}

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
            ->get();

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

        $repository->delete();

        return response()->json(['message' => 'Repositório removido com sucesso']);
    }
}

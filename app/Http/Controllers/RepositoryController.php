<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Repository;
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
            'github_owner' => 'required|string|max:255',
            'github_repo'  => 'required|string|max:255',
        ]);

        $repository = Repository::create([
            'tenant_id'    => $this->tenantId(),
            'github_owner' => $data['github_owner'],
            'github_repo'  => $data['github_repo'],
            'status'       => 'active',
        ]);

        return response()->json($repository, 201);
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
            'status'         => 'sometimes|string|in:active,syncing,error',
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

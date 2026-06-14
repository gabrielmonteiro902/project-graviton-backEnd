<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\OrbitConnection;
use App\Models\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrbitConnectionController extends Controller
{
    private function tenantId(): string
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin->tenant_id;
    }

    public function index(): JsonResponse
    {
        $connections = OrbitConnection::where('tenant_id', $this->tenantId())
            ->with(['primaryRepository', 'secondaryRepository'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($connections);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'primary_repository_id'   => 'required|uuid',
            'secondary_repository_id' => 'required|uuid|different:primary_repository_id',
            'name'                    => 'nullable|string|max:255',
        ]);

        $tenantId = $this->tenantId();

        $primaryExists = Repository::where('id', $data['primary_repository_id'])
            ->where('tenant_id', $tenantId)
            ->exists();

        $secondaryExists = Repository::where('id', $data['secondary_repository_id'])
            ->where('tenant_id', $tenantId)
            ->exists();

        if (! $primaryExists || ! $secondaryExists) {
            return response()->json([
                'error' => 'Um ou mais repositórios não foram encontrados.',
            ], 404);
        }

        $connection = OrbitConnection::create([
            'tenant_id'               => $tenantId,
            'name'                    => $data['name'] ?? null,
            'primary_repository_id'   => $data['primary_repository_id'],
            'secondary_repository_id' => $data['secondary_repository_id'],
        ]);

        $connection->load(['primaryRepository', 'secondaryRepository']);

        return response()->json($connection, 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $connection = OrbitConnection::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();

        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $connection->update($data);

        $connection->load(['primaryRepository', 'secondaryRepository']);

        return response()->json($connection);
    }

    public function destroy(string $id): JsonResponse
    {
        $connection = OrbitConnection::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();

        $connection->delete();

        return response()->json([
            'message' => 'Conexão orbital removida com sucesso.',
        ]);
    }
}

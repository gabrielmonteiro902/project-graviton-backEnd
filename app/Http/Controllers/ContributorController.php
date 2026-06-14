<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Contributor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContributorController extends Controller
{
    private function tenantId(): string
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin->tenant_id;
    }

    public function index(Request $request): JsonResponse
    {
        $query = Contributor::where('tenant_id', $this->tenantId());

        // Filtro de recrutamento: apenas devs abertos a propostas
        if ($request->boolean('hireable')) {
            $query->where('hireable', true);
        }

        $contributors = $query->orderBy('username')->paginate(20);

        return response()->json($contributors);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'github_id'  => 'required|integer',
            'username'   => 'required|string|max:255',
            'avatar_url' => 'nullable|string|url',
            'hireable'   => 'nullable|boolean',
            'location'   => 'nullable|string|max:255',
            'company'    => 'nullable|string|max:255',
        ]);

        // Upsert: se o dev já existe no tenant, atualiza o perfil
        $contributor = Contributor::updateOrCreate(
            ['tenant_id' => $this->tenantId(), 'github_id' => $data['github_id']],
            array_merge($data, ['tenant_id' => $this->tenantId()])
        );

        return response()->json($contributor, $contributor->wasRecentlyCreated ? 201 : 200);
    }

    public function show(string $id): JsonResponse
    {
        $contributor = Contributor::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();

        return response()->json($contributor);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $contributor = Contributor::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();

        $data = $request->validate([
            'avatar_url' => 'sometimes|nullable|string|url',
            'hireable'   => 'sometimes|boolean',
            'location'   => 'sometimes|nullable|string|max:255',
            'company'    => 'sometimes|nullable|string|max:255',
        ]);

        $contributor->update($data);

        return response()->json($contributor);
    }

    public function destroy(string $id): JsonResponse
    {
        $contributor = Contributor::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();

        $contributor->delete();

        return response()->json(['message' => 'Contribuidor removido com sucesso']);
    }
}

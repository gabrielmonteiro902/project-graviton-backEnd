<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Tenant::select('id', 'name', 'email', 'plan', 'created_at')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'id'    => 'required|string|unique:tenants,id|alpha_dash|max:100',
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:tenants,email',
            'plan'  => 'sometimes|string|in:free,starter,pro,enterprise',
        ]);

        $tenant = Tenant::create([
            'id'    => $request->id,
            'name'  => $request->name,
            'email' => $request->email,
            'plan'  => $request->input('plan', 'free'),
        ]);

        return response()->json([
            'id'         => $tenant->id,
            'name'       => $tenant->name,
            'email'      => $tenant->email,
            'plan'       => $tenant->plan,
            'created_at' => $tenant->created_at,
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);

        return response()->json([
            'id'         => $tenant->id,
            'name'       => $tenant->name,
            'email'      => $tenant->email,
            'plan'       => $tenant->plan,
            'created_at' => $tenant->created_at,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);

        $request->validate([
            'name'  => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:tenants,email,' . $id,
            'plan'  => 'sometimes|string|in:free,starter,pro,enterprise',
        ]);

        $tenant->update($request->only(['name', 'email', 'plan']));

        return response()->json([
            'id'         => $tenant->id,
            'name'       => $tenant->name,
            'email'      => $tenant->email,
            'plan'       => $tenant->plan,
            'updated_at' => $tenant->updated_at,
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->delete();

        return response()->json(['message' => 'Tenant removido com sucesso']);
    }
}

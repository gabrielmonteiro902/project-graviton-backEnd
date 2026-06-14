<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function showOwn(): JsonResponse
    {
        $tenant = app('current_tenant');

        return response()->json([
            'id'         => $tenant->id,
            'name'       => $tenant->name,
            'email'      => $tenant->email,
            'plan'       => $tenant->plan,
            'created_at' => $tenant->created_at,
        ]);
    }

    public function updateOwn(Request $request): JsonResponse
    {
        $tenant = app('current_tenant');

        $request->validate([
            'name'  => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:tenants,email,' . $tenant->id,
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
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdminRequest;
use App\Http\Requests\UpdateAdminRequest;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    private function tenantId(): string
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin->tenant_id;
    }

    public function index(): JsonResponse
    {
        $admins = Admin::where('tenant_id', $this->tenantId())
            ->select('id', 'name_admin', 'email_admin', 'tenant_id', 'created_at')
            ->get();

        return response()->json($admins);
    }

    public function store(StoreAdminRequest $request): JsonResponse
    {
        $admin = Admin::create([
            'name_admin'     => $request->name_admin,
            'email_admin'    => $request->email_admin,
            'password_admin' => Hash::make($request->password_admin),
            'tenant_id'      => $this->tenantId(),
        ]);

        return response()->json([
            'id'          => $admin->id,
            'name_admin'  => $admin->name_admin,
            'email_admin' => $admin->email_admin,
            'tenant_id'   => $admin->tenant_id,
            'created_at'  => $admin->created_at,
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $admin = Admin::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->select('id', 'name_admin', 'email_admin', 'tenant_id', 'created_at')
            ->firstOrFail();

        return response()->json($admin);
    }

    public function update(UpdateAdminRequest $request, string $id): JsonResponse
    {
        $admin = Admin::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();

        $data = $request->only(['name_admin', 'email_admin']);

        if ($request->filled('password_admin')) {
            $data['password_admin'] = Hash::make($request->password_admin);
        }

        $admin->update($data);

        return response()->json([
            'id'          => $admin->id,
            'name_admin'  => $admin->name_admin,
            'email_admin' => $admin->email_admin,
            'tenant_id'   => $admin->tenant_id,
            'updated_at'  => $admin->updated_at,
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $admin = Admin::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();

        $admin->delete();

        return response()->json(['message' => 'Admin removido com sucesso']);
    }
}

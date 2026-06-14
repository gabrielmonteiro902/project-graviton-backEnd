<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminAuthRegisterTest extends TestCase
{
    public function test_can_register_tenant_and_admin(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'tenant_id' => 'acme-corp',
            'tenant_name' => 'Acme Corporation',
            'tenant_email' => 'contact@acme.com',
            'tenant_plan' => 'starter',
            'name_admin' => 'John Doe',
            'email_admin' => 'john@acme.com',
            'password_admin' => 'secret123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'token',
                'tenant_id',
                'admin' => ['id', 'name_admin', 'email_admin'],
            ]);
    }

    public function test_register_fails_with_duplicate_tenant_id(): void
    {
        $data = [
            'tenant_id' => 'acme-corp',
            'tenant_name' => 'Acme Corporation',
            'tenant_email' => 'contact@acme.com',
            'tenant_plan' => 'starter',
            'name_admin' => 'John Doe',
            'email_admin' => 'john@acme.com',
            'password_admin' => 'secret123',
        ];

        $this->postJson('/api/v1/register', $data);
        $response = $this->postJson('/api/v1/register', $data);

        $response->assertStatus(422);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orbit_connections')) {
            return;
        }

        Schema::create('orbit_connections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id');
            $table->string('name')->nullable();
            $table->uuid('primary_repository_id');
            $table->uuid('secondary_repository_id');
            $table->timestamps();

            $table->foreign('tenant_id')
                  ->references('id')->on('tenants')
                  ->cascadeOnDelete();

            $table->foreign('primary_repository_id')
                  ->references('id')->on('repositories')
                  ->cascadeOnDelete();

            $table->foreign('secondary_repository_id')
                  ->references('id')->on('repositories')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orbit_connections');
    }
};

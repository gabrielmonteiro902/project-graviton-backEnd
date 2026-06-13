<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('repositories')) {
            return;
        }

        Schema::create('repositories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id');
            $table->string('github_owner');
            $table->string('github_repo');
            $table->string('status')->default('active'); // active, syncing, error
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->unique(['tenant_id', 'github_owner', 'github_repo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repositories');
    }
};

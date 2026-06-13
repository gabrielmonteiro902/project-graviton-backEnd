<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('contributions')) {
            return;
        }

        Schema::create('contributions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id');
            $table->foreignUuid('repository_id')->constrained('repositories')->cascadeOnDelete();
            $table->foreignUuid('contributor_id')->constrained('contributors')->cascadeOnDelete();
            $table->integer('commits_count')->default(0);
            $table->integer('additions')->default(0);
            $table->integer('deletions')->default(0);
            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->unique(['tenant_id', 'repository_id', 'contributor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contributions');
    }
};

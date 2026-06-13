<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('contributors')) {
            return;
        }

        Schema::create('contributors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id');
            $table->unsignedBigInteger('github_id');
            $table->string('username');
            $table->string('avatar_url')->nullable();
            $table->boolean('hireable')->default(false);
            $table->string('location')->nullable();
            $table->string('company')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->unique(['tenant_id', 'github_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contributors');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admins')) {
            return;
        }

        Schema::create('admins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name_admin');
            $table->string('email_admin');
            $table->string('password_admin');
            $table->string('tenant_id');
            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            // E-mail único por tenant (dois tenants podem ter o mesmo e-mail)
            $table->unique(['email_admin', 'tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};

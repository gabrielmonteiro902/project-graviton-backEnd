<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('admins', 'tenant_id')) {
            return;
        }

        Schema::table('admins', function (Blueprint $table) {
            // nullable para não quebrar com linhas antigas sem tenant
            $table->string('tenant_id')->nullable()->after('id');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('admins', 'tenant_id')) {
            return;
        }

        Schema::table('admins', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};

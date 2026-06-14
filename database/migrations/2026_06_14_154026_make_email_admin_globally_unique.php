<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropUnique(['email_admin', 'tenant_id']);
            $table->unique('email_admin');
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropUnique(['email_admin']);
            $table->unique(['email_admin', 'tenant_id']);
        });
    }
};

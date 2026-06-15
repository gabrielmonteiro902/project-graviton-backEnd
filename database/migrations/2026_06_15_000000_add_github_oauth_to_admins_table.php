<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            // Login via GitHub OAuth: contas OAuth não têm senha.
            $table->string('password_admin')->nullable()->change();

            $table->unsignedBigInteger('github_id')->nullable()->unique()->after('email_admin');
            $table->string('avatar_url')->nullable()->after('github_id');
            // Token OAuth do GitHub — guardado CRIPTOGRAFADO (cast 'encrypted' no model Admin).
            $table->text('github_token')->nullable()->after('avatar_url');
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropUnique(['github_id']);
            $table->dropColumn(['github_id', 'avatar_url', 'github_token']);
            // Mantém password_admin nullable: reverter quebraria contas OAuth existentes.
        });
    }
};

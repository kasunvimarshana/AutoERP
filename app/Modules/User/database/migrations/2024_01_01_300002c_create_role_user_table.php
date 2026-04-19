<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('tenant_id')->constrained(null, 'id', 'role_user_tenant_id_fk')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained(null, 'id', 'role_user_role_id_fk')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained(null, 'id', 'role_user_user_id_fk')->cascadeOnDelete();
            $table->primary(['tenant_id', 'role_id', 'user_id'], 'role_user_tenant_role_user_pk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
    }
};

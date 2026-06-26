<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'roles_tenant_fk')->restrictOnDelete();
            $table->string('name');
            $table->string('active_name_key')->nullable();
            $table->string('guard_name', 100);
            $table->string('system_key', 100)->nullable();
            $table->boolean('is_system')->default(false);
            $table->string('description', 500)->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->unsignedBigInteger('deleted_by_user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['id', 'tenant_id'], 'roles_id_tenant_uk');
            $table->unique(['tenant_id', 'guard_name', 'active_name_key'], 'roles_active_name_guard_uk');
            $table->unique(['tenant_id', 'system_key'], 'roles_system_key_uk');

            foreach (['created_by_user_id', 'updated_by_user_id', 'deleted_by_user_id'] as $column) {
                $table->foreign([$column, 'tenant_id'], 'roles_'.$column.'_tenant_fk')
                    ->references(['id', 'tenant_id'])
                    ->on('users')
                    ->restrictOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};

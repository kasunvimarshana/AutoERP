<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INITIAL_STATUS = 'invited';

    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->restrictOnDelete();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('username', 100)->nullable();
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('status', 30)->default(self::INITIAL_STATUS);
            $table->string('phone', 100)->nullable();
            $table->timestamp('credentials_ready_at')->nullable();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->unsignedBigInteger('deleted_by_user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['id', 'tenant_id'], 'users_id_tenant_uk');
            $table->unique(['tenant_id', 'email'], 'users_tenant_email_uk');
            $table->unique(['tenant_id', 'username'], 'users_tenant_username_uk');
            $table->index(['tenant_id', 'status', 'deleted_at'], 'users_tenant_status_idx');
            $table->index(['tenant_id', 'email'], 'users_tenant_email_idx');

            foreach (['created_by_user_id', 'updated_by_user_id', 'deleted_by_user_id'] as $column) {
                $table->foreign([$column, 'tenant_id'], 'users_'.$column.'_tenant_fk')
                    ->references(['id', 'tenant_id'])
                    ->on('users')
                    ->restrictOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

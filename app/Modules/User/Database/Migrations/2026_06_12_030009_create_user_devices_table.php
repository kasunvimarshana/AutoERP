<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'user_devices_tenant_fk')->restrictOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->char('device_token_hash', 64);
            $table->text('device_token_encrypted');
            $table->string('platform', 30);
            $table->string('device_name')->nullable();
            $table->dateTime('last_active_at');
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedBigInteger('registered_by_user_id');
            $table->unsignedBigInteger('revoked_by_user_id')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'user_devices_id_tenant_uk');
            $table->unique(['tenant_id', 'user_id', 'device_token_hash'], 'user_devices_token_uk');
            $table->index(['tenant_id', 'user_id', 'revoked_at'], 'user_devices_active_ix');
            $table->foreign(['user_id', 'tenant_id'], 'user_devices_user_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->cascadeOnDelete();
            $table->foreign(['registered_by_user_id', 'tenant_id'], 'user_devices_registered_by_user_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->foreign(['revoked_by_user_id', 'tenant_id'], 'user_devices_revoked_by_user_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};

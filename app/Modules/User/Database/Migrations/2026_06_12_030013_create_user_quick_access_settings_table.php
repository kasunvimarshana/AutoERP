<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_quick_access_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'user_quick_access_tenant_fk')->restrictOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->json('items');
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'user_quick_access_id_tenant_uk');
            $table->unique(['tenant_id', 'user_id'], 'user_quick_access_tenant_user_uk');
            $table->foreign(['user_id', 'tenant_id'], 'user_quick_access_user_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_quick_access_settings');
    }
};

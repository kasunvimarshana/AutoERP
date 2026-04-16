<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('key', 255);
            $table->json('value')->nullable();
            $table->string('group')->default('general')->index('idx_tenant_settings_group');
            $table->boolean('is_public')->default(false)->index('idx_tenant_settings_public');
            $table->timestamps();

            $table->unique(['tenant_id', 'key'], 'tenant_settings_tenant_key_uq');
            $table->index(['tenant_id', 'group'], 'idx_tenant_settings_tenant_group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_settings');
    }
};
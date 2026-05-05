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
            $table->foreignId('tenant_id')->constrained(null, 'id', 'tenant_settings_tenant_id_fk')->cascadeOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->string('key', 255);
            $table->json('value')->nullable();
            $table->string('group')->default('general')->index('tenant_settings_group_idx');
            $table->boolean('is_public')->default(false)->index('tenant_settings_public_idx');
            $table->timestamps();

            $table->unique(['tenant_id', 'key'], 'tenant_settings_tenant_id_key_uk');
            $table->index(['tenant_id', 'group'], 'tenant_settings_tenant_group_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_settings');
    }
};

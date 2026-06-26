<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_providers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->string('provider_key', 120);
            $table->string('name', 160);
            $table->string('driver', 80);
            $table->string('status', 30);
            $table->unsignedBigInteger('row_version')->default(1);
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'auth_provider_id_tenant_uk');
            $table->unique(['tenant_id', 'provider_key'], 'auth_provider_key_uk');
            $table->index(['tenant_id', 'status'], 'auth_provider_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_providers');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id', 'payment_terms_tenant_id_fk')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('days')->default(30);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'name'], 'payment_terms_tenant_name_uk');
            $table->index(['tenant_id', 'is_active'], 'payment_terms_tenant_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_terms');
    }
};

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
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('days')->default(30);
            $table->unsignedInteger('discount_days')->nullable();
            $table->decimal('discount_rate', 8, 4)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
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

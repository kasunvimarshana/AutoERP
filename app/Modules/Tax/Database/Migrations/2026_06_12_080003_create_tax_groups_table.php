<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->string('code', 100);
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'organization_unit_id', 'code'], 'tax_groups_scope_code_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'is_default'], 'tax_groups_default_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_groups');
    }
};

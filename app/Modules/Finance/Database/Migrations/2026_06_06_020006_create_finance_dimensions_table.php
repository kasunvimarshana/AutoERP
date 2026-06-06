<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_dimensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->string('code', 100);
            $table->string('name');
            $table->enum('dimension_type', [
                'department',
                'project',
                'cost_center',
                'branch',
                'customer',
                'supplier',
                'employee',
                'vehicle',
                'custom',
            ]);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'code'], 'finance_dimensions_tenant_code_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_dimensions');
    }
};

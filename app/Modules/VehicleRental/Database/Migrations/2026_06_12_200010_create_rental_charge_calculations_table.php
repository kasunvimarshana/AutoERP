<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_charge_calculations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('agreement_id')->constrained('rental_agreements')->cascadeOnDelete();
            $table->string('source_type', 50);
            $table->unsignedBigInteger('source_id');
            $table->string('calculation_type', 30);
            $table->decimal('quantity', 20, 6);
            $table->decimal('rate', 20, 6);
            $table->decimal('amount', 20, 6);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(
                ['agreement_id', 'source_type', 'source_id', 'calculation_type'],
                'rental_charge_calculations_source_uk',
            );
            $table->index(['tenant_id', 'organization_unit_id'], 'rental_charge_calculations_tenant_org_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_charge_calculations');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_vehicle_replacements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'rental_vehicle_replacements_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->json('metadata')->nullable();
            $table->string('replacement_number', 100);
            $table->foreignId('agreement_id');
            $table->foreignId('old_allocation_id');
            $table->foreignId('new_allocation_id')->nullable();
            $table->dateTime('replacement_at');
            $table->string('reason_code', 50)->nullable();
            $table->text('reason')->nullable();
            $table->string('billing_continuity_rule', 30)->default('continue_period');
            $table->string('status', 30)->default('draft');
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->dateTime('reversed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'replacement_number'], 'rental_vehicle_replacements_tenant_number_uk');
            $table->unique('old_allocation_id', 'rental_vehicle_replacements_old_uk');
            $table->unique('new_allocation_id', 'rental_vehicle_replacements_new_uk');
            $table->index(['agreement_id', 'replacement_at'], 'rental_vehicle_replacements_agreement_at_ix');

            $table->unique(['id', 'tenant_id'], 'rental_vehicle_replacements_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'rental_vehicle_replacements_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['agreement_id', 'tenant_id'], 'rental_vehicle_replacements_agreement_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_agreements')
                ->cascadeOnDelete();
            $table->foreign(['old_allocation_id', 'tenant_id'], 'rental_vehicle_replacements_old_allocation_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_vehicle_allocations')
                ->restrictOnDelete();
            $table->foreign(['new_allocation_id', 'tenant_id'], 'rental_vehicle_replacements_new_allocation_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_vehicle_allocations')
                ->restrictOnDelete();

            $table->foreign(['completed_by', 'tenant_id'], 'rental_vehicle_replacements_completed_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['reversed_by', 'tenant_id'], 'rental_vehicle_replacements_reversed_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['created_by', 'tenant_id'], 'rental_vehicle_replacements_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['updated_by', 'tenant_id'], 'rental_vehicle_replacements_updated_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_vehicle_replacements');
    }
};

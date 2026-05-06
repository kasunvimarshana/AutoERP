<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_contracts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->string('contract_number');
            // Polymorphic party: the entity we are contracting with
            $table->enum('party_type', ['customer', 'supplier', 'partner']);  // 'partner' = rental_partners
            $table->unsignedBigInteger('party_id');                           // id in customers, suppliers, or rental_partners

            $table->date('contract_date');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('terms')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->foreignId('signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['draft', 'active', 'completed', 'terminated'])->default('draft');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint per tenant/org
            $table->unique(['tenant_id', 'org_unit_id', 'contract_number'], 'rental_contracts_number_uk');
            // Index for looking up contracts by party
            $table->index(['tenant_id', 'party_type', 'party_id'], 'rental_contracts_party_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_contracts');
    }
};

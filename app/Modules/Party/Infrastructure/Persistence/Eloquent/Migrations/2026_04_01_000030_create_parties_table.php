<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->string('name');
            $table->string('type')->comment('customer, supplier, lead, both');
            $table->string('tax_number')->nullable();
            $table->string('registration_number')->nullable();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('credit_limit', 20, 4)->nullable();
            $table->unsignedInteger('payment_terms_days')->default(30);
            $table->string('status')->default('active')->comment('active, inactive, blocked');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // portal login linkage
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'organization_unit_id', 'type'], 'parties_type_idx');
            $table->index(['tenant_id', 'organization_unit_id', 'name'], 'parties_name_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parties');
    }
};

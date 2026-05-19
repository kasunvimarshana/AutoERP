<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // portal login linkage
            $table->string('code')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('type')->default('individual')->comment('individual, company');
            $table->string('tax_number')->nullable();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('credit_limit', 20, 4)->nullable();
            $table->unsignedInteger('payment_terms_days')->default(30);
            $table->string('status')->default('active')->comment('active, inactive, blocked');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreignId('ap_account_id')->nullable()->constrained('accounts', 'id')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'type'], 'suppliers_type_idx');
            $table->index(['tenant_id', 'name'], 'suppliers_name_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};

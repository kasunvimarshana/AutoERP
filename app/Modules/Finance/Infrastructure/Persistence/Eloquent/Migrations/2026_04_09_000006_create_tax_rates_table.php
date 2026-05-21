<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('tax_group_id')->constrained('tax_groups')->cascadeOnDelete();
            $table->string('name')->comment('e.g., "VAT 18%", "GST 5%"');
            $table->string('type')->default('percentage')->comment('percentage, fixed');
            $table->decimal('rate', 20, 4)->comment('percentage (e.g., 18.00 for 18%)');
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->boolean('is_compound')->default(false)->comment('tax on tax');
            $table->boolean('is_active')->default(true);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};

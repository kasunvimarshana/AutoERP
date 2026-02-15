<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tax_jurisdictions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('jurisdiction_type', ['country', 'state', 'city', 'postal_code', 'custom']);
            $table->string('country_code', 2)->nullable();
            $table->string('state_code', 10)->nullable();
            $table->string('city_name')->nullable();
            $table->string('postal_code')->nullable();
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->foreignId('tax_group_id')->nullable()->constrained('tax_groups')->nullOnDelete();
            $table->integer('priority')->default(0);
            $table->boolean('is_reverse_charge')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('rules')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'country_code', 'state_code']);
            $table->index(['tenant_id', 'jurisdiction_type', 'is_active']);
            $table->index(['tenant_id', 'postal_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_jurisdictions');
    }
};

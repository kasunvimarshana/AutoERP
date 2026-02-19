<?php

declare(strict_types=1);

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
        Schema::create('fiscal_periods', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id');
            $table->ulid('organization_id');
            $table->ulid('fiscal_year_id');
            $table->string('name', 100);
            $table->string('code', 20);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('open');
            $table->timestamp('closed_at')->nullable();
            $table->ulid('closed_by')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->ulid('locked_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('fiscal_year_id')->references('id')->on('fiscal_years')->onDelete('cascade');

            // Indexes
            $table->index(['tenant_id', 'organization_id']);
            $table->index(['tenant_id', 'fiscal_year_id']);
            $table->index(['tenant_id', 'start_date']);
            $table->index(['tenant_id', 'end_date']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'code']);

            // Composite indexes
            $table->index(['tenant_id', 'organization_id', 'status']);
            $table->index(['tenant_id', 'fiscal_year_id', 'start_date']);
            $table->unique(['tenant_id', 'fiscal_year_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fiscal_periods');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('party_id')->constrained('parties');
            $table->foreignId('original_job_card_id')->nullable()->constrained('service_job_cards')->nullOnDelete();
            $table->string('return_number');
            $table->string('status')->default('draft');
            $table->date('return_date');
            $table->string('return_reason')->nullable();
            $table->decimal('grand_total', 20, 4)->default(0);
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'organization_unit_id', 'return_number'], 'service_returns_return_number_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_returns');
    }
};

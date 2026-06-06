<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')
                ->constrained('tenants', 'id')
                ->cascadeOnDelete()
                ->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')
                ->nullable()
                ->constrained('organization_units', 'id')
                ->nullOnDelete()
                ->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('document_type')->comment('Generic sequence key supplied by a module, such as invoice, order, journal, voucher, or agreement.');
            $table->string('prefix')->default('');
            $table->string('suffix')->default('');
            $table->unsignedInteger('padding')->default(5);
            $table->bigInteger('next_number')->default(1);
            $table->enum('period_type', ['yearly', 'monthly', 'infinite'])->default('yearly');
            $table->string('period_value')->nullable()->comment('e.g., 2025');
            $table->string('scope_key')->comment('Non-null organization/period scope used for portable uniqueness.');
            $table->unsignedBigInteger('created_by')->nullable()->index('sequences_created_by_idx');
            $table->unsignedBigInteger('updated_by')->nullable()->index('sequences_updated_by_idx');
            $table->unsignedBigInteger('deleted_by')->nullable()->index('sequences_deleted_by_idx');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['tenant_id', 'document_type', 'scope_key'],
                'sequences_document_period_uk'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sequences');
    }
};

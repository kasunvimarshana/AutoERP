<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->foreignId('invoice_id')->constrained('invoices', 'id')->cascadeOnDelete();

            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('reason')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at');
            $table->json('metadata_json')->nullable()->comment('Non-domain metadata such as workflow action or integration reason codes');

            $table->timestamps();

            $table->index(['tenant_id', 'invoice_id', 'changed_at'], 'invoice_status_histories_invoice_idx');
            $table->index(['tenant_id', 'to_status', 'changed_at'], 'invoice_status_histories_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_status_histories');
    }
};

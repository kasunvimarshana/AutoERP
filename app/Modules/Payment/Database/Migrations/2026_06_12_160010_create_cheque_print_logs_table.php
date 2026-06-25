<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cheque_print_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->foreignId('payment_id')->constrained('payments', 'id')->cascadeOnDelete();
            $table->foreignId('payment_line_id')->nullable()->constrained('payment_lines', 'id')->nullOnDelete();
            $table->foreignId('cheque_template_id')->constrained('cheque_templates', 'id');
            $table->unsignedBigInteger('printed_by')->nullable();
            $table->timestamp('printed_at');
            $table->enum('print_status', ['previewed', 'printed', 'cancelled'])->default('printed');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'cheque_print_logs_tenant_org_idx');
            $table->index(['payment_id', 'printed_at'], 'cheque_print_logs_payment_date_idx');
            $table->index(['payment_line_id', 'printed_at'], 'cheque_print_logs_line_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cheque_print_logs');
    }
};

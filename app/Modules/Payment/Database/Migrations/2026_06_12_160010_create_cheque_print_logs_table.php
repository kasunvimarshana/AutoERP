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
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('payment_id');
            $table->foreignId('payment_line_id')->nullable();
            $table->foreignId('cheque_template_id');
            $table->unsignedBigInteger('printed_by')->nullable();
            $table->dateTime('printed_at');
            $table->enum('print_status', ['previewed', 'printed', 'cancelled'])->default('printed');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'cheque_print_logs_tenant_org_idx');
            $table->index(['payment_id', 'printed_at'], 'cheque_print_logs_payment_date_idx');
            $table->index(['payment_line_id', 'printed_at'], 'cheque_print_logs_line_date_idx');

            $table->unique(['id', 'tenant_id'], 'cheque_print_logs_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'cheque_print_logs_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['payment_id', 'tenant_id'], 'cheque_print_logs_payment_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('payments')
                ->cascadeOnDelete();
            $table->foreign(['payment_line_id', 'tenant_id'], 'cheque_print_logs_payment_line_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('payment_lines')
                ->restrictOnDelete();
            $table->foreign(['cheque_template_id', 'tenant_id'], 'cheque_print_logs_cheque_template_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('cheque_templates')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cheque_print_logs');
    }
};

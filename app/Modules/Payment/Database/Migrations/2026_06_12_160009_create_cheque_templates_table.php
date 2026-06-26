<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cheque_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'cheque_templates_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('template_name');
            $table->decimal('page_width_mm', 8, 3);
            $table->decimal('page_height_mm', 8, 3);
            $table->decimal('date_x_mm', 8, 3);
            $table->decimal('date_y_mm', 8, 3);
            $table->decimal('payee_x_mm', 8, 3);
            $table->decimal('payee_y_mm', 8, 3);
            $table->decimal('amount_x_mm', 8, 3);
            $table->decimal('amount_y_mm', 8, 3);
            $table->decimal('amount_words_x_mm', 8, 3);
            $table->decimal('amount_words_y_mm', 8, 3);
            $table->decimal('cheque_number_x_mm', 8, 3)->nullable();
            $table->decimal('cheque_number_y_mm', 8, 3)->nullable();
            $table->decimal('font_size', 5, 2)->default('12');
            $table->string('font_family')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('default_scope_key', 160)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'organization_unit_id'], 'cheque_templates_tenant_org_ix');
            $table->index(['tenant_id', 'is_active', 'is_default'], 'cheque_templates_active_default_ix');
            $table->unique('default_scope_key', 'cheque_templates_default_scope_uk');

            $table->unique(['id', 'tenant_id'], 'cheque_templates_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'cheque_templates_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cheque_templates');
    }
};

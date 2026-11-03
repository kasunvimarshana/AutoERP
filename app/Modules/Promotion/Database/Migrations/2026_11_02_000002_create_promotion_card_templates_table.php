<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_card_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'promotion_card_templates_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('code', 80);
            $table->string('name');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 20);
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('created_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'code'], 'promotion_card_templates_tenant_code_uk');
            $table->unique(['id', 'tenant_id'], 'promotion_card_templates_id_tenant_uk');
            $table->index(['tenant_id', 'status', 'starts_on', 'ends_on'], 'promotion_card_templates_active_ix');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'promotion_card_templates_org_tenant_fk')->references(['id', 'tenant_id'])->on('organization_units')->restrictOnDelete();
            $table->foreign(['created_by', 'tenant_id'], 'promotion_card_templates_created_by_fk')->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_card_templates');
    }
};

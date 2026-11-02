<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_offers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'promotion_offers_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('code', 80);
            $table->string('name');
            $table->foreignId('qualifying_item_id');
            $table->unsignedInteger('paid_quantity');
            $table->foreignId('reward_item_id');
            $table->unsignedInteger('reward_quantity');
            $table->string('count_scope', 20);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 20);
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('created_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'code'], 'promotion_offers_tenant_code_uk');
            $table->unique(['id', 'tenant_id'], 'promotion_offers_id_tenant_uk');
            $table->index(['tenant_id', 'status', 'starts_on', 'ends_on'], 'promotion_offers_active_ix');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'promotion_offers_org_tenant_fk')->references(['id', 'tenant_id'])->on('organization_units')->restrictOnDelete();
            $table->foreign(['qualifying_item_id', 'tenant_id'], 'promotion_offers_qualifying_item_fk')->references(['id', 'tenant_id'])->on('items')->restrictOnDelete();
            $table->foreign(['reward_item_id', 'tenant_id'], 'promotion_offers_reward_item_fk')->references(['id', 'tenant_id'])->on('items')->restrictOnDelete();
            $table->foreign(['created_by', 'tenant_id'], 'promotion_offers_created_by_fk')->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_offers');
    }
};

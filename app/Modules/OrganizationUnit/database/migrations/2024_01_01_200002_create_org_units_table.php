<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('org_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained(null, 'id', 'org_units_tenant_id_fk')->cascadeOnDelete();
            $table->foreignId('type_id')->nullable()->constrained('org_unit_types', 'id', 'org_units_type_id_fk')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('org_units', 'id', 'org_units_parent_id_fk')->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('path')->nullable(); // materialized path for quick tree queries
            $table->unsignedInteger('depth')->default(0);
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->integer('_lft')->default(0);
            $table->integer('_rgt')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Financial defaults
            $table->foreignId('default_revenue_account_id')->nullable(); // will reference accounts later
            $table->foreignId('default_expense_account_id')->nullable(); // will reference accounts later
            $table->foreignId('default_asset_account_id')->nullable(); // will reference accounts later
            $table->foreignId('default_liability_account_id')->nullable(); // will reference accounts later

            // Physical links
            $table->foreignId('warehouse_id')->nullable(); // will reference warehouses later
            $table->foreignId('manager_user_id')->nullable(); // will reference users later

            $table->unique(['tenant_id', 'code'], 'org_units_tenant_id_code_uk');
            $table->index(['tenant_id', 'parent_id'], 'org_units_tenant_parent_idx');
            $table->index(['tenant_id', 'path'], 'org_units_tenant_path_idx');
            $table->index(['tenant_id', '_lft', '_rgt'], 'org_units_tenant_id_lft_rgt_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('org_units');
    }
};

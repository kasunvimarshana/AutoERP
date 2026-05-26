<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_setting_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('key');
            $table->string('name');
            $table->string('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('tenant_setting_groups', 'id')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable()->index('tenant_setting_groups_created_by_idx');
            $table->unsignedBigInteger('updated_by')->nullable()->index('tenant_setting_groups_updated_by_idx');
            $table->unsignedBigInteger('deleted_by')->nullable()->index('tenant_setting_groups_deleted_by_idx');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'key'], 'tenant_setting_groups_tenant_key_uk');
            $table->index(['tenant_id', 'parent_id', 'sort_order'], 'tenant_setting_groups_tree_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_setting_groups');
    }
};

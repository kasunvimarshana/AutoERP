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
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('type_id')->nullable()->constrained('org_unit_types')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('org_units')->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('path')->nullable(); // materialized path for quick tree queries
            $table->unsignedInteger('depth')->default(0);
            $table->foreignId('manager_user_id')->nullable(); // will reference users later
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->integer('_lft')->default(0);
            $table->integer('_rgt')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code'], 'org_units_code_unique');
            $table->index(['tenant_id', 'parent_id'], 'idx_org_units_tenant_parent');
            $table->index(['tenant_id', 'path'], 'idx_org_units_tenant_path');
            $table->index(['tenant_id', '_lft', '_rgt']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('org_units');
    }
};
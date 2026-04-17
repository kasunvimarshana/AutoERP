<?php

declare(strict_types=1);


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('path')->nullable();
            $table->unsignedInteger('depth')->default(0);
            $table->enum('type', ['zone', 'aisle', 'rack', 'shelf', 'bin', 'staging', 'dispatch'])->default('bin');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_pickable')->default(true);
            $table->boolean('is_receivable')->default(true);
            $table->decimal('capacity', 15, 4)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'warehouse_id', 'code'], 'uq_wh_locations_tenant_warehouse_code');
            $table->index(['tenant_id', 'parent_id'], 'idx_wh_locations_tenant_parent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_locations');
    }
};
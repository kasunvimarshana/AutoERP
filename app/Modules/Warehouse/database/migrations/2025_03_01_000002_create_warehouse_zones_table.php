<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_zones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->string('type', 100);
            $table->text('description')->nullable();
            $table->decimal('capacity', 12, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('parent_zone_id')->nullable();
            $table->integer('_lft')->default(0);
            $table->integer('_rgt')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('warehouse_id');
            $table->index('tenant_id');
            $table->index('type');
            $table->index(['tenant_id', '_lft', '_rgt']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_zones');
    }
};

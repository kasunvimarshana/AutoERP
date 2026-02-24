<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_locations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('warehouse_id')->index();
            $table->string('name');
            $table->string('code');
            $table->string('type')->default('bulk_storage');
            $table->uuid('parent_id')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['warehouse_id', 'code']);
        });
    }
    public function down(): void { Schema::dropIfExists('inventory_locations'); }
};

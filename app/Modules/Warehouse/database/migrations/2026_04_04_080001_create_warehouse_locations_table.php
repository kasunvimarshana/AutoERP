<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('warehouse_locations', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('tenant_id');
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name'); $table->string('code'); $table->string('type')->default('bin');
            $table->unsignedSmallInteger('level')->default(0); $table->boolean('is_active')->default(true);
            $table->timestamps(); $table->softDeletes();
            $table->unique(['warehouse_id','code']); $table->index(['tenant_id','warehouse_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('warehouse_locations'); }
};

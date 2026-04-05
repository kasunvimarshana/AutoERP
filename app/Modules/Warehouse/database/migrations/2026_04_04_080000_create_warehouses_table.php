<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('tenant_id');
            $table->string('name'); $table->string('code'); $table->string('type')->default('standard');
            $table->text('address'); $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('manager_id')->nullable(); $table->json('metadata')->nullable();
            $table->timestamps(); $table->softDeletes();
            $table->unique(['tenant_id','code']); $table->index('tenant_id');
        });
    }
    public function down(): void { Schema::dropIfExists('warehouses'); }
};

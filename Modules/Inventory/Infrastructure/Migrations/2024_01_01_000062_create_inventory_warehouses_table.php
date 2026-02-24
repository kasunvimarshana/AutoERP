<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_warehouses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('name');
            $table->string('code');
            $table->json('address')->nullable();
            $table->uuid('responsible_user_id')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
        });
    }
    public function down(): void { Schema::dropIfExists('inventory_warehouses'); }
};

<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category', 100);
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->decimal('purchase_cost', 15, 4);
            $table->decimal('residual_value', 15, 4)->default(0);
            $table->unsignedSmallInteger('useful_life_months')->default(0);
            $table->string('depreciation_method', 30)->default('straight_line');
            $table->unsignedBigInteger('asset_account_id')->nullable();
            $table->unsignedBigInteger('depreciation_account_id')->nullable();
            $table->string('status', 30)->default('active');
            $table->date('purchase_date');
            $table->date('disposal_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'category']);
        });
    }
    public function down(): void { Schema::dropIfExists('fixed_assets'); }
};

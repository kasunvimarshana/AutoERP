<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('asset_category_id')->nullable()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('location')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 18, 8)->default(0);
            $table->decimal('salvage_value', 18, 8)->default(0);
            $table->unsignedInteger('useful_life_years')->default(0);
            $table->string('depreciation_method')->default('straight_line');
            $table->decimal('annual_depreciation', 18, 8)->default(0);
            $table->decimal('book_value', 18, 8)->default(0);
            $table->decimal('disposal_value', 18, 8)->nullable();
            $table->string('status')->default('active');
            $table->text('disposal_notes')->nullable();
            $table->timestamp('disposed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};

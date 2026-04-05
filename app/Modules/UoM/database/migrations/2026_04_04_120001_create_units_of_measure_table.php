<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('units_of_measure', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('tenant_id');
            $table->foreignId('category_id')->constrained('uom_categories')->cascadeOnDelete();
            $table->string('name'); $table->string('symbol');
            $table->boolean('is_base')->default(false);
            $table->decimal('conversion_factor',15,8)->default(1);
            $table->string('type')->default('base');
            $table->boolean('is_active')->default(true);
            $table->timestamps(); $table->softDeletes(); $table->index('tenant_id');
        });
    }
    public function down(): void { Schema::dropIfExists('units_of_measure'); }
};

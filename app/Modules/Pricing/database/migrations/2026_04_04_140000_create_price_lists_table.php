<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('price_lists', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('tenant_id');
            $table->string('name'); $table->string('currency',3)->default('USD');
            $table->decimal('discount_percent',5,2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->date('valid_from')->nullable(); $table->date('valid_to')->nullable();
            $table->timestamps(); $table->softDeletes(); $table->index('tenant_id');
        });
    }
    public function down(): void { Schema::dropIfExists('price_lists'); }
};

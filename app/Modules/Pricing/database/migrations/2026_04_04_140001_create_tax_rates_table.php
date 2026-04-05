<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('tenant_id');
            $table->string('name'); $table->string('code');
            $table->decimal('rate',5,2); $table->string('type')->default('percentage');
            $table->boolean('is_compound')->default(false); $table->boolean('is_active')->default(true);
            $table->string('applies_to')->nullable();
            $table->timestamps(); $table->softDeletes();
            $table->unique(['tenant_id','code']); $table->index('tenant_id');
        });
    }
    public function down(): void { Schema::dropIfExists('tax_rates'); }
};

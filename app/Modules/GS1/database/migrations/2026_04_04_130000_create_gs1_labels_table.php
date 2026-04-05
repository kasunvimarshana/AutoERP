<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('gs1_labels', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('tenant_id'); $table->unsignedBigInteger('product_id');
            $table->string('gs1_type'); $table->string('gs1_value')->unique();
            $table->string('batch_number')->nullable(); $table->string('lot_number')->nullable();
            $table->string('serial_number')->nullable(); $table->string('expiry_date')->nullable();
            $table->decimal('net_weight',10,4)->nullable(); $table->string('country_of_origin',3)->nullable();
            $table->timestamps(); $table->softDeletes(); $table->index(['tenant_id','product_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('gs1_labels'); }
};

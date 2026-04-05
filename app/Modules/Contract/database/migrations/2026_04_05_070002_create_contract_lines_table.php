<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contract_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->string('description');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->decimal('quantity', 15, 4)->default(1);
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->decimal('total_price', 15, 4)->default(0);
            $table->date('due_date')->nullable();
            $table->boolean('is_delivered')->default(false);
            $table->dateTime('delivered_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['contract_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('contract_lines'); }
};

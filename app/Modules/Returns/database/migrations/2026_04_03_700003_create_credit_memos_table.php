<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('credit_memos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('memo_number', 100);
            $table->foreignId('stock_return_id')->constrained('stock_returns')->cascadeOnDelete();
            $table->decimal('amount', 15, 4);
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->char('currency', 3)->default('USD');
            $table->text('notes')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'memo_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_memos');
    }
};

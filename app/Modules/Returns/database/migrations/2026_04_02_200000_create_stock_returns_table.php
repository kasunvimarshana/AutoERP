<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('reference_number', 100)->unique();
            $table->string('return_type', 50);
            $table->string('status', 50)->default('draft');
            $table->unsignedBigInteger('party_id');
            $table->string('party_type', 50);
            $table->unsignedBigInteger('original_reference_id')->nullable();
            $table->string('original_reference_type', 100)->nullable();
            $table->string('return_reason', 255)->nullable();
            $table->decimal('total_amount', 15, 4)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->boolean('restock')->default(true);
            $table->unsignedBigInteger('restock_location_id')->nullable();
            $table->decimal('restocking_fee', 15, 4)->default(0);
            $table->boolean('credit_memo_issued')->default(false);
            $table->string('credit_memo_reference', 100)->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'return_type']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'reference_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_returns');
    }
};

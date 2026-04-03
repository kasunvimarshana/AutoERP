<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_memos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('reference_number', 100)->unique();
            $table->unsignedBigInteger('stock_return_id')->nullable();
            $table->unsignedBigInteger('party_id');
            $table->string('party_type', 50);
            $table->string('status', 50)->default('draft');
            $table->decimal('amount', 15, 4)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->timestamp('issue_date')->nullable();
            $table->timestamp('applied_date')->nullable();
            $table->timestamp('voided_date')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'stock_return_id']);
            $table->index(['tenant_id', 'party_id', 'party_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_memos');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pos_receipts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->index();
            $table->uuid('transaction_id')->index();
            $table->string('format')->default('thermal'); // thermal, standard, a4
            $table->text('content');
            $table->timestamp('printed_at')->nullable();
            $table->integer('print_count')->default(0);
            $table->timestamps();
            
            $table->foreign('transaction_id')
                ->references('id')
                ->on('pos_transactions')
                ->onDelete('cascade');
                
            $table->index(['tenant_id', 'transaction_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_receipts');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_receipts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('transaction_id')->constrained('pos_transactions')->cascadeOnDelete();
            
            $table->string('receipt_number')->unique();
            $table->timestamp('receipt_date');
            $table->enum('receipt_type', ['sale', 'return', 'void'])->default('sale');
            
            $table->text('content');
            $table->enum('format', ['text', 'html', 'pdf'])->default('text');
            
            $table->timestamp('printed_at')->nullable();
            $table->foreignId('printed_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamp('email_sent_at')->nullable();
            $table->string('email_sent_to')->nullable();
            
            $table->json('metadata')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'receipt_number']);
            $table->index(['tenant_id', 'transaction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_receipts');
    }
};

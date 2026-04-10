<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturnsTable extends Migration
{
    public function up()
    {
        Schema::create('returns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('return_number')->unique();
            $table->enum('return_type', ['purchase', 'sales']);
            // $table->nullableMorphs('reference'); // purchase_order or sales_order
            $table->uuid('reference_id');
            $table->string('reference_type');
            $table->string('return_number')->unique();
            $table->uuid('customer_id')->nullable();
            $table->uuid('supplier_id')->nullable();
            $table->date('return_date');
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'processing', 'completed', 'rejected', 'cancelled']);
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->decimal('restocking_fee', 20, 6)->default(0);
            $table->decimal('shipping_fee', 20, 6)->default(0);
            $table->decimal('total_amount', 20, 6);
            $table->uuid('credit_memo_id')->nullable();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->uuid('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['return_number', 'return_type', 'status']);
            $table->index(['reference_id', 'reference_type']);
            $table->index(['customer_id', 'supplier_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('returns');
    }
}
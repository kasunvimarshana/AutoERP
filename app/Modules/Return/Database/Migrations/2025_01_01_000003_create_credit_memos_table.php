<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCreditMemosTable extends Migration
{
    public function up()
    {
        Schema::create('credit_memos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('memo_number')->unique();
            $table->uuid('return_id')->nullable();
            $table->enum('memo_type', ['customer', 'supplier']);
            $table->uuid('party_id');
            $table->decimal('amount', 20, 6);
            $table->decimal('tax_amount', 20, 6)->default(0);
            $table->decimal('total_amount', 20, 6);
            $table->enum('status', ['draft', 'issued', 'applied', 'void', 'cancelled']);
            $table->date('issue_date');
            $table->date('valid_until')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestamps();
            
            $table->foreign('return_id')->references('id')->on('returns');
            $table->index(['memo_number', 'status']);
            $table->index(['party_id', 'memo_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('credit_memos');
    }
}
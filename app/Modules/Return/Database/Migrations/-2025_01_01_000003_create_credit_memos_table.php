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
            $table->nullableMorphs('reference'); // return or direct
            $table->uuid('customer_id')->nullable();
            $table->uuid('supplier_id')->nullable();
            $table->decimal('amount', 20, 6);
            $table->timestamp('issued_at');
            $table->enum('status', ['issued', 'applied', 'void'])->default('issued');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('credit_memos');
    }
}
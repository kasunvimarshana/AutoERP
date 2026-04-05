<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('payable_type');
            $table->unsignedBigInteger('payable_id');
            $table->decimal('amount', 15, 4);
            $table->string('currency', 3)->default('USD');
            $table->string('payment_method');
            $table->string('status')->default('pending');
            $table->string('direction');       // inbound|outbound
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->date('payment_date');
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['payable_type', 'payable_id']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

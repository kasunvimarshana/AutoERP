<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('original_payment_id');
            $table->decimal('amount', 15, 4);
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('pending');
            $table->text('reason')->nullable();
            $table->string('reference')->nullable();
            $table->date('refund_date');
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('tenant_id');
            $table->index('original_payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};

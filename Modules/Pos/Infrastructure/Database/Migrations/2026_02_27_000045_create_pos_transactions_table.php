<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('pos_session_id');
            $table->string('transaction_number');
            $table->string('status')->default('draft')->comment('draft/hold/completed/refunded/cancelled');
            $table->decimal('subtotal', 20, 4);
            $table->decimal('discount_amount', 20, 4)->default('0.0000');
            $table->decimal('tax_amount', 20, 4)->default('0.0000');
            $table->decimal('total_amount', 20, 4);
            $table->decimal('paid_amount', 20, 4)->default('0.0000');
            $table->decimal('change_due', 20, 4)->default('0.0000');
            $table->boolean('is_synced')->default(false);
            $table->boolean('created_offline')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'transaction_number']);
            $table->foreign('pos_session_id')->references('id')->on('pos_sessions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_transactions');
    }
};
